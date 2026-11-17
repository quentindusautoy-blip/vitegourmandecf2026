<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/extra_actions.php';
require_once __DIR__ . '/mongo_stats.php';
require_once __DIR__ . '/mail_notifications.php';

function post_actions(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    if (in_array($action, ['update_profile','update_order','save_hours','save_menu','save_dish','link_dish','unlink_dish'], true)) {
        extra_actions($action);
    }
    if ($action === 'sync_stats') {
        $user = require_staff();
        if ($user['role'] !== 'admin') { http_response_code(403); exit('Accès interdit.'); }
        try { $n = stats_sync_all(); }
        catch (Throwable $error) { error_log('Vite & Gourmand : sync MongoDB impossible'); throw new RuntimeException('Synchronisation indisponible. Vérifiez la connexion MongoDB Atlas.'); }
        flash('success', $n . ' commande(s) synchronisée(s) dans MongoDB.');
        jump('staff');
    }
    switch ($action) {
        case 'register':
            $first=required('first_name',80); $last=required('last_name',80);
            $email=filter_var(required('email',190),FILTER_VALIDATE_EMAIL);
            if (!$email) throw new InvalidArgumentException('Adresse email incorrecte.');
            $phone=required('phone',25); $address=required('address',255);
            $password=(string)($_POST['password']??'');
            if (!password_valid($password)) throw new InvalidArgumentException('Mot de passe : au moins 10 caractères, avec majuscule, minuscule, chiffre et caractère spécial.');
            $q=db()->prepare('INSERT INTO users(first_name,last_name,email,phone,address,password_hash) VALUES (?,?,?,?,?,?)');
            try { $q->execute([$first,$last,$email,$phone,$address,password_hash($password,PASSWORD_DEFAULT)]); }
            catch (PDOException $e) { if ($e->getCode()==='23000') throw new InvalidArgumentException('Cette adresse email est déjà utilisée.'); throw $e; }
            session_regenerate_id(true); $_SESSION['uid']=(int)db()->lastInsertId();
            $sent = vg_mail_welcome(['first_name' => $first, 'email' => $email]);
            flash($sent ? 'success' : 'error', $sent
                ? 'Compte créé. Un email de bienvenue a été envoyé.'
                : 'Compte créé, mais l’email de bienvenue n’a pas pu être envoyé.'); jump('account');
        case 'login':
            $email=required('email',190); $pass=(string)($_POST['password']??'');
            $q=db()->prepare('SELECT id,password_hash,active FROM users WHERE email=?'); $q->execute([$email]); $u=$q->fetch();
            if (!$u || !$u['active'] || !password_verify($pass,$u['password_hash'])) {
                throw new InvalidArgumentException('Identifiants incorrects.');
            }
            session_regenerate_id(true); $_SESSION['uid']=(int)$u['id'];
            flash('success','Connexion réussie.'); jump('account');
        case 'logout':
            $_SESSION=[]; session_regenerate_id(true);
            flash('success','Vous êtes déconnecté.'); jump('home');
        case 'contact':
            $subject=required('subject',160); $mail=filter_var(required('email',190),FILTER_VALIDATE_EMAIL); $body=required('body',5000);
            if (!$mail) throw new InvalidArgumentException('Adresse email incorrecte.');
            if (!empty($_SESSION['contact_last']) && time() - (int)$_SESSION['contact_last'] < 60) {
                throw new InvalidArgumentException('Merci d’attendre une minute avant d’envoyer un autre message.');
            }
            $q=db()->prepare('INSERT INTO contact_messages(subject,email,body) VALUES (?,?,?)');
            $q->execute([$subject,$mail,$body]);
            $_SESSION['contact_last'] = time();
            $sent = vg_mail_contact((string)$mail, $subject, $body);
            flash($sent ? 'success' : 'error', $sent
                ? 'Votre demande a été enregistrée et transmise à notre équipe.'
                : 'Votre demande a été enregistrée, mais l’email n’a pas pu être transmis.');
            jump('contact');
        case 'request_password_reset':
            $email = filter_var(required('email',190), FILTER_VALIDATE_EMAIL);
            if (!$email) throw new InvalidArgumentException('Saisissez une adresse email valide.');
            vg_request_password_reset((string)$email);
            flash('success', 'Si cette adresse correspond à un compte actif, un lien de réinitialisation a été envoyé. Pensez à vérifier les spams.');
            jump('forgot-password');
        case 'reset_password':
            $token = (string)($_POST['token'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');
            if ($password !== $confirm) throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
            vg_change_password_with_token($token, $password);
            unset($_SESSION['uid']);
            session_regenerate_id(true);
            flash('success','Mot de passe modifié. Vous pouvez vous connecter.');
            jump('login');
        case 'place_order':
            $u=require_login(); $menuId=(int)($_POST['menu_id']??0); $people=(int)($_POST['people']??0);
            $date=required('event_date',10); $time=required('delivery_time',5);
            $address=required('delivery_address',255); $city=required('delivery_city',100);
            $dateObj=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
            if (!$dateObj || $dateObj->format('Y-m-d')!==$date || $dateObj < new DateTimeImmutable('today')) {
                throw new InvalidArgumentException('La date de prestation doit être valide et future.');
            }
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/',$time)) throw new InvalidArgumentException('Horaire incorrect.');
            $isBordeaux=mb_strtolower(trim($city))==='bordeaux';
            $distance=null; $shipping=0;
            if (!$isBordeaux) {
                $raw=(string)($_POST['distance_km']??'');
                if (!is_numeric($raw) || (float)$raw <=0 || (float)$raw > 1000)
                    throw new InvalidArgumentException('Indiquez la distance kilométrique estimée hors Bordeaux.');
                $distance=round((float)$raw,2);
                $shipping=500+(int)round($distance*59); // 5 € + 0,59 €/km, en centimes
            }
            $pdo=db(); $pdo->beginTransaction();
            try {
                $q=$pdo->prepare('SELECT * FROM menus WHERE id=? AND active=1 FOR UPDATE');
                $q->execute([$menuId]); $menu=$q->fetch();
                if (!$menu) throw new InvalidArgumentException('Menu introuvable.');
                if ((int)$menu['stock']<1) throw new InvalidArgumentException('Ce menu n’est plus disponible.');
                if ($people < (int)$menu['min_people'] || $people>500)
                    throw new InvalidArgumentException('Le nombre de personnes doit respecter le minimum et ne pas dépasser 500.');
                if ($dateObj < (new DateTimeImmutable('today'))->modify('+'.(int)$menu['lead_days'].' days'))
                    throw new InvalidArgumentException('Ce menu nécessite au moins '.(int)$menu['lead_days'].' jours de préavis.');
                $subtotal=(int)round((int)$menu['price_cents']*$people/(int)$menu['min_people']);
                $discount=($people >= (int)$menu['min_people']+5) ? (int)round($subtotal*0.10) : 0;
                $total=$subtotal-$discount+$shipping;
                $i=$pdo->prepare('INSERT INTO orders(user_id,menu_id,people,event_date,delivery_time,delivery_address,delivery_city,distance_km,subtotal_cents,discount_cents,shipping_cents,total_cents) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $i->execute([$u['id'],$menuId,$people,$date,$time,$address,$city,$distance,$subtotal,$discount,$shipping,$total]);
                $orderId=(int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO order_history(order_id,status,changed_by) VALUES (?,"pending",?)')->execute([$orderId,$u['id']]);
                $pdo->prepare('UPDATE menus SET stock=stock-1 WHERE id=?')->execute([$menuId]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            stats_try_sync_one($orderId);
            $emailSent = vg_mail_order_confirmation($orderId);
            flash($emailSent ? 'success' : 'error',
                'Commande n°'.$orderId.' enregistrée. Montant : '.euro($total).
                ' (livraison estimative si hors Bordeaux). '.
                ($emailSent ? 'Email de confirmation envoyé.' : 'L’email de confirmation n’a pas pu être envoyé.'));
            jump('account');
        case 'cancel_order':
            $u=require_login(); $id=(int)($_POST['order_id']??0);
            $pdo=db(); $pdo->beginTransaction();
            try {
                $q=$pdo->prepare('SELECT menu_id,status FROM orders WHERE id=? AND user_id=? FOR UPDATE');
                $q->execute([$id,$u['id']]); $o=$q->fetch();
                if (!$o || $o['status']!=='pending') throw new InvalidArgumentException('Cette commande ne peut plus être annulée.');
                $pdo->prepare('UPDATE orders SET status="cancelled" WHERE id=?')->execute([$id]);
                $pdo->prepare('UPDATE menus SET stock=stock+1 WHERE id=?')->execute([$o['menu_id']]);
                $pdo->prepare('INSERT INTO order_history(order_id,status,changed_by) VALUES (?,"cancelled",?)')->execute([$id,$u['id']]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            stats_try_sync_one($id);
            flash('success','Commande annulée.'); jump('account');
        case 'review':
            $u=require_login(); $id=(int)($_POST['order_id']??0); $rating=(int)($_POST['rating']??0); $comment=required('comment',2000);
            if ($rating<1 || $rating>5) throw new InvalidArgumentException('La note doit être entre 1 et 5.');
            $q=db()->prepare('SELECT 1 FROM orders WHERE id=? AND user_id=? AND status="completed"');
            $q->execute([$id,$u['id']]); if (!$q->fetch()) throw new InvalidArgumentException('Avis autorisé uniquement après la fin de votre commande.');
            try { db()->prepare('INSERT INTO reviews(order_id,user_id,rating,comment) VALUES (?,?,?,?)')->execute([$id,$u['id'],$rating,$comment]); }
            catch (PDOException $e) { if ($e->getCode()==='23000') throw new InvalidArgumentException('Un avis existe déjà pour cette commande.'); throw $e; }
            flash('success','Merci ! Votre avis est en attente de validation.'); jump('account');
        case 'change_status':
            $u=require_staff(); $id=(int)($_POST['order_id']??0); $target=required('status',30);
            $allowed=['pending'=>['accepted','cancelled'],'accepted'=>['preparing','cancelled'],
                'preparing'=>['shipping','cancelled'],'shipping'=>['delivered'],
                'delivered'=>['awaiting_material','completed'],'awaiting_material'=>['completed']];
            $pdo=db(); $pdo->beginTransaction();
            try {
                $q=$pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE'); $q->execute([$id]); $o=$q->fetch();
                if (!$o || !in_array($target,$allowed[$o['status']]??[],true)) throw new InvalidArgumentException('Transition de statut impossible.');
                $reason=null; $method=null;
                if ($target==='cancelled') {
                    $reason=required('cancellation_reason',1000); $method=required('contact_method',10);
                    if (!in_array($method,['phone','email'],true)) throw new InvalidArgumentException('Méthode de contact incorrecte.');
                }
                $pdo->prepare('UPDATE orders SET status=?, cancellation_reason=?, contact_method=? WHERE id=?')->execute([$target,$reason,$method,$id]);
                if ($target==='cancelled') $pdo->prepare('UPDATE menus SET stock=stock+1 WHERE id=?')->execute([$o['menu_id']]);
                $pdo->prepare('INSERT INTO order_history(order_id,status,changed_by) VALUES (?,?,?)')->execute([$id,$target,$u['id']]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            stats_try_sync_one($id);
            $sent = vg_mail_order_status($id, $target);
            flash($sent ? 'success' : 'error', $sent
                ? 'Statut mis à jour. Notification client envoyée.'
                : 'Statut mis à jour, mais la notification client n’a pas pu être envoyée.');
            jump('staff');
        case 'edit_menu':
            require_staff(); $id=(int)($_POST['menu_id']??0);
            $stock=filter_var($_POST['stock']??null,FILTER_VALIDATE_INT);
            if ($stock===false || $stock<0 || $stock>100000) throw new InvalidArgumentException('Stock incorrect.');
            $q=db()->prepare('UPDATE menus SET stock=? WHERE id=?'); $q->execute([$stock,$id]);
            flash('success','Stock mis à jour.'); jump('staff');
        case 'moderate_review':
            require_staff(); $id=(int)($_POST['review_id']??0); $status=required('status',10);
            if (!in_array($status,['approved','rejected'],true)) throw new InvalidArgumentException('Décision incorrecte.');
            db()->prepare('UPDATE reviews SET status=? WHERE id=? AND status="pending"')->execute([$status,$id]);
            flash('success','Avis modéré.'); jump('staff');
        case 'create_employee':
            $u=require_staff(); if ($u['role']!=='admin') { http_response_code(403); exit('Accès interdit.'); }
            $mail=filter_var(required('email',190),FILTER_VALIDATE_EMAIL); $pass=(string)($_POST['password']??'');
            if (!$mail || !password_valid($pass)) throw new InvalidArgumentException('Email ou mot de passe non conforme.');
            $first = required('first_name',80);
            db()->prepare('INSERT INTO users(first_name,last_name,email,phone,address,password_hash,role) VALUES (?,?,?,?,?,?,"employee")')
                ->execute([$first,required('last_name',80),$mail,required('phone',25),'Adresse à compléter',password_hash($pass,PASSWORD_DEFAULT)]);
            $sent = vg_mail_employee_created((string)$mail, $first);
            flash($sent ? 'success' : 'error', $sent
                ? 'Compte employé créé. Un email d’information sans mot de passe a été envoyé.'
                : 'Compte employé créé, mais l’email n’a pas pu être envoyé.'); jump('staff');
        case 'disable_employee':
            $u=require_staff(); if ($u['role']!=='admin') { http_response_code(403); exit('Accès interdit.'); }
            db()->prepare('UPDATE users SET active=0 WHERE id=? AND role="employee"')->execute([(int)($_POST['employee_id']??0)]);
            flash('success','Compte employé désactivé.'); jump('staff');
        default:
            throw new InvalidArgumentException('Action inconnue.');
    }
}
