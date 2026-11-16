<?php
declare(strict_types=1);

/**
 * Fonctionnalités complémentaires de l'ECF.
 * Toutes les opérations doivent être autorisées et vérifiées côté serveur.
 */
function extra_actions(string $action): void {
    switch ($action) {
        case 'update_profile':
            $u = require_login();
            $first = required('first_name', 80);
            $last = required('last_name', 80);
            $phone = required('phone', 25);
            $address = required('address', 255);
            $email = filter_var(required('email', 190), FILTER_VALIDATE_EMAIL);
            if (!$email) throw new InvalidArgumentException('Adresse email invalide.');
            try {
                $s = db()->prepare('UPDATE users SET first_name=?,last_name=?,phone=?,address=?,email=? WHERE id=? AND active=1');
                $s->execute([$first, $last, $phone, $address, $email, $u['id']]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') throw new InvalidArgumentException('Cet email est déjà utilisé.');
                throw $e;
            }
            flash('success', 'Vos informations ont été enregistrées.');
            jump('account');

        case 'update_order':
            $u = require_login();
            $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $people = filter_var($_POST['people'] ?? null, FILTER_VALIDATE_INT);
            $date = required('event_date', 10);
            $time = required('delivery_time', 5);
            $address = required('delivery_address', 255);
            $city = required('delivery_city', 100);
            if (!$orderId || !$people || $people > 500) throw new InvalidArgumentException('Commande ou nombre de personnes incorrect.');
            $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$eventDate || $eventDate->format('Y-m-d') !== $date || $eventDate < new DateTimeImmutable('today')) {
                throw new InvalidArgumentException('La date doit être valide et à venir.');
            }
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                throw new InvalidArgumentException('Heure de livraison invalide.');
            }
            $inBordeaux = mb_strtolower(trim($city)) === 'bordeaux';
            $distance = null;
            $shipping = 0;
            if (!$inBordeaux) {
                $raw = (string)($_POST['distance_km'] ?? '');
                if (!is_numeric($raw) || (float)$raw <= 0 || (float)$raw > 1000) {
                    throw new InvalidArgumentException('Indiquez une distance estimée hors Bordeaux (km).');
                }
                $distance = round((float)$raw, 2);
                $shipping = 500 + (int)round($distance * 59);
            }
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $s = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=? FOR UPDATE');
                $s->execute([$orderId, $u['id']]);
                $order = $s->fetch();
                if (!$order || $order['status'] !== 'pending') {
                    throw new InvalidArgumentException('Cette commande a déjà été acceptée ou n’existe pas.');
                }
                $s = $pdo->prepare('SELECT * FROM menus WHERE id=? FOR UPDATE');
                $s->execute([$order['menu_id']]);
                $menu = $s->fetch();
                if (!$menu) throw new RuntimeException('Le menu de cette commande est introuvable.');
                if ($people < (int)$menu['min_people']) throw new InvalidArgumentException('Nombre minimum : ' . (int)$menu['min_people'] . ' personnes.');
                if ($eventDate < (new DateTimeImmutable('today'))->modify('+' . (int)$menu['lead_days'] . ' days')) {
                    throw new InvalidArgumentException('Préavis insuffisant pour ce menu.');
                }
                $subtotal = (int)round((int)$menu['price_cents'] * $people / (int)$menu['min_people']);
                $discount = $people >= (int)$menu['min_people'] + 5 ? (int)round($subtotal * 0.10) : 0;
                $total = $subtotal - $discount + $shipping;
                $s = $pdo->prepare('UPDATE orders SET people=?,event_date=?,delivery_time=?,delivery_address=?,delivery_city=?,distance_km=?,subtotal_cents=?,discount_cents=?,shipping_cents=?,total_cents=? WHERE id=? AND user_id=? AND status="pending"');
                $s->execute([$people, $date, $time, $address, $city, $distance, $subtotal, $discount, $shipping, $total, $orderId, $u['id']]);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            flash('success', 'Commande modifiée. Nouveau total : ' . euro($total) . '.');
            jump('account');

        case 'save_hours':
            require_staff();
            $day = filter_var($_POST['day_no'] ?? null, FILTER_VALIDATE_INT);
            $hours = required('hours', 60);
            if (!$day || $day < 1 || $day > 7) throw new InvalidArgumentException('Jour invalide.');
            db()->prepare('UPDATE opening_hours SET hours=? WHERE day_no=?')->execute([$hours, $day]);
            flash('success', 'Horaires mis à jour.');
            jump('staff');

        case 'save_menu':
            require_staff();
            $id = filter_var($_POST['menu_id'] ?? '0', FILTER_VALIDATE_INT);
            if ($id === false || $id < 0) throw new InvalidArgumentException('Menu invalide.');
            $title = required('title', 150);
            $desc = required('description', 5000);
            $theme = required('theme', 45);
            $diet = required('diet', 45);
            $conditions = required('conditions_text', 5000);
            $min = filter_var($_POST['min_people'] ?? null, FILTER_VALIDATE_INT);
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
            $lead = filter_var($_POST['lead_days'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($_POST['price_cents'] ?? null, FILTER_VALIDATE_INT);
            if (!$min || $min < 1 || $min > 500 || $stock === false || $stock < 0 || $stock > 100000 || $lead === false || $lead < 0 || $lead > 365 || !$price || $price < 1 || $price > 100000000) {
                throw new InvalidArgumentException('Nombre de convives, stock, délai ou prix incorrect.');
            }
            $active = isset($_POST['active']) ? 1 : 0;
            $pdo = db();
            if ($id === 0) {
                $s = $pdo->prepare('INSERT INTO menus(title,description,theme,diet,min_people,price_cents,stock,lead_days,conditions_text,active) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $s->execute([$title,$desc,$theme,$diet,$min,$price,$stock,$lead,$conditions,$active]);
            } else {
                $s = $pdo->prepare('UPDATE menus SET title=?,description=?,theme=?,diet=?,min_people=?,price_cents=?,stock=?,lead_days=?,conditions_text=?,active=? WHERE id=?');
                $s->execute([$title,$desc,$theme,$diet,$min,$price,$stock,$lead,$conditions,$active,$id]);
                if (!$s->rowCount()) {
                    // rowCount=0 peut simplement signifier qu'aucune donnée n'a changé.
                    $c=$pdo->prepare('SELECT 1 FROM menus WHERE id=?');$c->execute([$id]);
                    if (!$c->fetchColumn()) throw new InvalidArgumentException('Ce menu n’existe pas.');
                }
            }
            flash('success', 'Menu enregistré.');
            jump('staff');

        case 'save_dish':
            require_staff();
            $id = filter_var($_POST['dish_id'] ?? '0', FILTER_VALIDATE_INT);
            $name = required('name',160);
            $kind = required('kind',10);
            $allergens = trim((string)($_POST['allergens'] ?? ''));
            if ($id === false || $id < 0 || !in_array($kind, ['entree','plat','dessert'], true) || mb_strlen($allergens) > 255) {
                throw new InvalidArgumentException('Données du plat incorrectes.');
            }
            if ($id === 0) db()->prepare('INSERT INTO dishes(name,kind,allergens) VALUES (?,?,?)')->execute([$name,$kind,$allergens?:null]);
            else db()->prepare('UPDATE dishes SET name=?,kind=?,allergens=? WHERE id=?')->execute([$name,$kind,$allergens?:null,$id]);
            flash('success', 'Plat enregistré.');
            jump('staff');

        case 'link_dish':
            require_staff();
            $menu = filter_var($_POST['menu_id'] ?? null, FILTER_VALIDATE_INT);
            $dish = filter_var($_POST['dish_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$menu || !$dish) throw new InvalidArgumentException('Menu ou plat invalide.');
            db()->prepare('INSERT IGNORE INTO menu_dishes(menu_id,dish_id) VALUES (?,?)')->execute([$menu,$dish]);
            flash('success', 'Plat ajouté au menu.');
            jump('staff');

        case 'unlink_dish':
            require_staff();
            $menu = filter_var($_POST['menu_id'] ?? null, FILTER_VALIDATE_INT);
            $dish = filter_var($_POST['dish_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$menu || !$dish) throw new InvalidArgumentException('Menu ou plat invalide.');
            db()->prepare('DELETE FROM menu_dishes WHERE menu_id=? AND dish_id=?')->execute([$menu,$dish]);
            flash('success', 'Plat retiré du menu.');
            jump('staff');

        default:
            throw new InvalidArgumentException('Action complémentaire inconnue.');
    }
}
