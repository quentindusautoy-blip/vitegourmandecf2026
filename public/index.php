<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/actions.php';

$page=(string)($_GET['page'] ?? 'home');
$pages=['home','menus','menu','register','login','order','account','staff','contact','legal','terms'];
if (!in_array($page,$pages,true)) { http_response_code(404); $page='not-found'; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try { post_actions(); }
    catch (InvalidArgumentException|RuntimeException $e) { flash('error',$e->getMessage()); jump(in_array($page,$pages,true)?$page:'home', ['id'=>(int)($_GET['id']??0)]); }
    catch (PDOException $e) { error_log($e->getMessage()); flash('error','Problème technique ou donnée déjà utilisée.'); jump(in_array($page,$pages,true)?$page:'home'); }
}
try { $me=current_user(); $hours=db()->query('SELECT * FROM opening_hours ORDER BY day_no')->fetchAll(); }
catch (PDOException $e) { http_response_code(503); exit('Connexion à la base impossible. Vérifiez l’import SQL et la configuration MariaDB.'); }
// Contrôler les droits avant tout affichage (pour que les redirections HTTP fonctionnent).
if (in_array($page, ['account', 'order', 'staff'], true) && !$me) {
    flash('error', 'Connectez-vous pour continuer.'); jump('login');
}
if ($page === 'staff' && !in_array($me['role'] ?? '', ['employee', 'admin'], true)) {
    http_response_code(403); exit('Accès interdit.');
}
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(ucfirst($page)) ?> · Vite & Gourmand</title>
<meta name="description" content="Vite & Gourmand, traiteur pour vos événements à Bordeaux.">
<link rel="stylesheet" href="assets/style.css"><script defer src="assets/app.js"></script>
</head><body>
<a href="#contenu" class="skip">Aller au contenu</a>
<header class="site-header"><div class="container header-inner">
<a class="logo" href="<?=e(url('home'))?>"><span>Vite <em>&</em> Gourmand</span><small>Traiteur à Bordeaux</small></a>
<nav aria-label="Navigation principale" class="nav">
<a href="<?=e(url('home'))?>">Accueil</a><a href="<?=e(url('menus'))?>">Nos menus</a><a href="<?=e(url('contact'))?>">Contact</a>
<?php if ($me): ?><a href="<?=e(url('account'))?>">Mon compte</a><?php if(in_array($me['role'],['admin','employee'],true)):?><a href="<?=e(url('staff'))?>">Gestion</a><?php endif; ?>
<form method="post" action="<?=e(url('home'))?>" class="inline"><?=csrf_input()?><input type="hidden" name="action" value="logout"><button class="nav-action" type="submit">Déconnexion</button></form>
<?php else: ?><a class="nav-action" href="<?=e(url('login'))?>">Connexion</a><?php endif; ?>
</nav></div></header>
<main id="contenu" class="container">
<?php if($flash): ?><div class="notice <?=($flash['type']==='error')?'error':'success'?>" role="status"><?=e($flash['message'])?></div><?php endif; ?>
<?php if($page==='home'): ?>
<section class="hero"><div><p class="eyebrow">Traiteur depuis 25 ans · Bordeaux</p><h1>Le goût de se retrouver.</h1><p>Julie et José préparent des menus généreux pour vos moments importants, des repas en famille aux grandes célébrations.</p><a class="btn" href="<?=e(url('menus'))?>">Découvrir nos menus</a></div><div class="hero-art" aria-hidden="true"><span>V<span class="amp">&</span>G</span><small>Des instants à savourer</small></div></section>
<section class="section"><p class="eyebrow">Notre savoir-faire</p><h2>Votre événement, notre cuisine</h2><p>Des plats pensés pour les convives, une préparation soignée et des menus qui évoluent au fil des saisons.</p><div class="features"><article><h3>Fait pour vous</h3><p>Menus adaptés à chaque occasion et différentes préférences alimentaires.</p></article><article><h3>À partager</h3><p>Des prestations à commander selon le nombre de convives.</p></article><article><h3>En toute clarté</h3><p>Allergènes, délais et conditions précisés avant la commande.</p></article></div></section>
<section class="section"><div class="section-title"><h2>Les menus du moment</h2><a href="<?=e(url('menus'))?>">Voir tous les menus</a></div><div class="cards">
<?php foreach(db()->query('SELECT * FROM menus WHERE active=1 ORDER BY id LIMIT 3')->fetchAll() as $m): ?>
<article class="menu-card"><div class="card-cover">✳</div><div class="card-body"><p class="tag"><?=e($m['theme'])?> · <?=e($m['diet'])?></p><h3><?=e($m['title'])?></h3><p><?=e($m['description'])?></p><div class="card-foot"><strong><?=euro((int)$m['price_cents'])?></strong><a href="<?=e(url('menu',['id'=>$m['id']]))?>">Détails</a></div></div></article>
<?php endforeach; ?></div></section>
<section class="section"><h2>Ils nous font confiance</h2><p>Les avis sont publiés uniquement après validation par notre équipe.</p><div class="cards">
<?php $q=db()->query('SELECT r.rating,r.comment,u.first_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.status="approved" ORDER BY r.created_at DESC LIMIT 3'); foreach($q as $v): ?>
<blockquote class="review"><strong><?=str_repeat('★',(int)$v['rating'])?></strong><p><?=e($v['comment'])?></p><footer><?=e($v['first_name'])?></footer></blockquote>
<?php endforeach; ?></div></section>
<?php elseif($page==='menus'): ?>
<section class="page-heading"><p class="eyebrow">La carte</p><h1>Nos menus pour toutes les occasions</h1><p>Affinez votre recherche sans recharger la page.</p></section>
<div class="filter-panel" id="filters"><label>Thème<select id="filter-theme"><option value="">Tous</option><?php foreach(db()->query('SELECT DISTINCT theme FROM menus WHERE active=1 ORDER BY theme') as $r):?><option><?=e($r['theme'])?></option><?php endforeach;?></select></label>
<label>Régime<select id="filter-diet"><option value="">Tous</option><?php foreach(db()->query('SELECT DISTINCT diet FROM menus WHERE active=1 ORDER BY diet') as $r):?><option><?=e($r['diet'])?></option><?php endforeach;?></select></label>
<label>Prix maximum (€)<input id="filter-max" type="number" min="0" placeholder="Tous"></label><label>Prix minimum (€)<input id="filter-min" type="number" min="0" placeholder="Aucun"></label>
<label>Nombre de convives<input id="filter-people" type="number" min="1" placeholder="Tous"></label></div>
<p role="status" id="filter-count" aria-live="polite"></p><div class="cards" id="menu-grid">
<?php foreach(db()->query('SELECT * FROM menus WHERE active=1 ORDER BY id') as $m): ?>
<article class="menu-card filter-card" data-theme="<?=e($m['theme'])?>" data-diet="<?=e($m['diet'])?>" data-price="<?=((int)$m['price_cents'])/100?>" data-people="<?=(int)$m['min_people']?>"><div class="card-cover">✳</div><div class="card-body"><p class="tag"><?=e($m['theme'])?> · <?=e($m['diet'])?></p><h2><?=e($m['title'])?></h2><p><?=e($m['description'])?></p><p>Dès <?=(int)$m['min_people']?> personnes · <?=(int)$m['stock']?> commandes disponibles</p><div class="card-foot"><strong><?=euro((int)$m['price_cents'])?></strong><a href="<?=e(url('menu',['id'=>$m['id']]))?>">Voir le détail</a></div></div></article>
<?php endforeach; ?></div>
<?php elseif($page==='menu'): ?>
<?php $m=menu_by_id((int)($_GET['id']??0)); if(!$m): ?><h1>Menu indisponible</h1><?php else: ?>
<section class="page-heading"><a href="<?=e(url('menus'))?>">← Tous les menus</a><p class="eyebrow"><?=e($m['theme'])?> · <?=e($m['diet'])?></p><h1><?=e($m['title'])?></h1><p><?=e($m['description'])?></p></section>
<div class="detail-layout"><section><div class="detail-cover" aria-label="Illustration décorative">✳</div><h2>Au programme</h2>
<?php $q=db()->prepare('SELECT d.* FROM dishes d JOIN menu_dishes md ON md.dish_id=d.id WHERE md.menu_id=? ORDER BY FIELD(d.kind,"entree","plat","dessert"),d.id');$q->execute([$m['id']]);foreach($q as $d):?><div class="dish"><span><?=e(ucfirst($d['kind']))?></span><strong><?=e($d['name'])?></strong><small>Allergènes : <?=e($d['allergens']?:'Aucun indiqué — à confirmer auprès du traiteur')?></small></div><?php endforeach;?>
<h2>Conditions importantes</h2><div class="notice"><strong>À lire avant de commander :</strong><p><?=e($m['conditions_text'])?></p><p>Préavis minimum : <?=(int)$m['lead_days']?> jours.</p></div></section>
<aside class="purchase-box"><p class="tag">Pour <?=(int)$m['min_people']?> convives minimum</p><h2><?=euro((int)$m['price_cents'])?></h2><p>Stock : <?=(int)$m['stock']?> commandes</p><p>Tarif recalculé en fonction du nombre de convives. −10 % dès 5 convives supplémentaires.</p><?php if((int)$m['stock']>0):?><a class="btn full" href="<?=e(url($me?'order':'login',$me?['id'=>$m['id']]:[]))?>">Commander</a><?php else:?><p>Indisponible</p><?php endif;?></aside></div>
<?php endif; ?>
<?php elseif($page==='register' || $page==='login'): ?>
<div class="auth-wrapper"><section class="form-card"><p class="eyebrow">Espace client</p><h1><?=$page==='login'?'Connexion':'Créer un compte'?></h1><form method="post" action="<?=e(url($page))?>"><?=csrf_input()?><input type="hidden" name="action" value="<?=$page==='login'?'login':'register'?>">
<?php if($page==='register'): ?><div class="two"><label>Prénom<input name="first_name" maxlength="80" required autocomplete="given-name"></label><label>Nom<input name="last_name" maxlength="80" required autocomplete="family-name"></label></div><label>Téléphone<input name="phone" maxlength="25" required autocomplete="tel"></label><label>Adresse postale<input name="address" maxlength="255" required autocomplete="street-address"></label><?php endif; ?>
<label>Email<input name="email" type="email" maxlength="190" autocomplete="email" required></label><label>Mot de passe<input name="password" type="password" minlength="<?=$page==='register'?'10':'1'?>" autocomplete="<?=$page==='login'?'current-password':'new-password'?>" required></label>
<?php if($page==='register'): ?><small>10 caractères minimum, majuscule, minuscule, chiffre et caractère spécial.</small><?php endif; ?>
<button class="btn full" type="submit"><?=$page==='login'?'Se connecter':'Créer mon compte'?></button></form><p><?=$page==='login'?'Pas encore de compte ?':'Déjà client ?'?> <a href="<?=e(url($page==='login'?'register':'login'))?>"><?=$page==='login'?'S’inscrire':'Se connecter'?></a></p></section></div>
<?php elseif($page==='order'): ?>
<?php $u=require_login();$m=menu_by_id((int)($_GET['id']??0)); if(!$m):?><h1>Menu indisponible</h1><?php else: ?>
<section class="page-heading"><h1>Commander <?=e($m['title'])?></h1><p>Le tarif final sera vérifié par le serveur au moment de la commande.</p></section><div class="auth-wrapper wide"><section class="form-card"><form method="post" action="<?=e(url('order',['id'=>$m['id']]))?>"><?=csrf_input()?><input type="hidden" name="action" value="place_order"><input type="hidden" name="menu_id" value="<?=(int)$m['id']?>">
<p>Client : <strong><?=e($u['first_name'].' '.$u['last_name'])?></strong> · <?=e($u['email'])?> · <?=e($u['phone'])?></p><div class="two"><label>Date de prestation<input type="date" name="event_date" required min="<?=date('Y-m-d',strtotime('+'.(int)$m['lead_days'].' days'))?>"></label><label>Heure de livraison<input type="time" name="delivery_time" required></label></div><label>Adresse de livraison<input name="delivery_address" value="<?=e($u['address'])?>" required></label><label>Ville<input name="delivery_city" id="delivery-city" value="Bordeaux" required></label><label>Distance routière estimée en km (uniquement hors Bordeaux)<input type="number" id="distance-km" name="distance_km" step="0.01" min="0.01" max="1000" placeholder="À faire confirmer par le traiteur"></label><label>Nombre de personnes (minimum <?=(int)$m['min_people']?>)<input type="number" name="people" id="people" min="<?=(int)$m['min_people']?>" max="500" value="<?=(int)$m['min_people']?>" required data-base-price="<?=(int)$m['price_cents']?>" data-min-people="<?=(int)$m['min_people']?>"></label>
<div class="notice"><strong>Estimation du tarif</strong><p id="price-preview">Calcul en cours…</p><small>Hors Bordeaux, distance déclarative à vérifier. 5 € + 0,59 €/km.</small></div><div class="notice"><strong>Conditions du menu</strong><p><?=e($m['conditions_text'])?></p></div><button class="btn full" type="submit">Valider la commande</button></form></section></div>
<?php endif; ?>
<?php elseif($page==='account'): ?>
<?php $u=require_login(); ?>
<section class="page-heading"><p class="eyebrow">Espace personnel</p><h1>Bonjour <?=e($u['first_name'])?></h1><p>Gérez vos coordonnées et vos commandes.</p></section>
<details class="order-card"><summary><strong>Modifier mes informations personnelles</strong></summary>
<form method="post" action="<?=e(url('account'))?>">
<?=csrf_input()?><input type="hidden" name="action" value="update_profile">
<div class="two"><label>Prénom<input name="first_name" required maxlength="80" value="<?=e($u['first_name'])?>"></label><label>Nom<input name="last_name" required maxlength="80" value="<?=e($u['last_name'])?>"></label></div>
<div class="two"><label>Email<input name="email" type="email" required maxlength="190" value="<?=e($u['email'])?>"></label><label>Téléphone<input name="phone" required maxlength="25" value="<?=e($u['phone'])?>"></label></div>
<label>Adresse postale<input name="address" required maxlength="255" value="<?=e($u['address'])?>"></label>
<button type="submit" class="btn">Enregistrer mes coordonnées</button>
</form></details>
<h2>Mes commandes</h2>
<?php $q=db()->prepare('SELECT o.*,m.title,m.min_people,m.lead_days FROM orders o JOIN menus m ON m.id=o.menu_id WHERE user_id=? ORDER BY o.created_at DESC');$q->execute([$u['id']]);$orders=$q->fetchAll();if(!$orders):?><p>Aucune commande pour le moment. <a href="<?=e(url('menus'))?>">Découvrir nos menus</a></p><?php endif;?>
<?php foreach($orders as $o):?>
<article class="order-card"><div class="order-top"><div><p class="eyebrow">Commande n°<?=(int)$o['id']?></p><h3><?=e($o['title'])?></h3><p><?=e($o['event_date'])?> à <?=e(substr($o['delivery_time'],0,5))?> · <?=(int)$o['people']?> personnes</p><p>Total : <strong><?=euro((int)$o['total_cents'])?></strong></p></div><span class="status"><?=e($o['status'])?></span></div>
<details><summary>Historique du suivi</summary><ol><?php $hist=db()->prepare('SELECT status,changed_at FROM order_history WHERE order_id=? ORDER BY id');$hist->execute([$o['id']]);foreach($hist as $h):?><li><?=e($h['changed_at'])?> — <?=e($h['status'])?></li><?php endforeach;?></ol></details>
<?php if($o['status']==='pending'):?>
<details><summary><strong>Modifier cette commande (avant acceptation)</strong></summary>
<p>Le menu choisi n'est pas modifiable. Le tarif sera recalculé à l'enregistrement selon les conditions actuelles de ce menu.</p>
<form method="post" action="<?=e(url('account'))?>"><?=csrf_input()?><input type="hidden" name="action" value="update_order"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>">
<div class="two"><label>Date de prestation<input type="date" name="event_date" required min="<?=date('Y-m-d',strtotime('+'.(int)$o['lead_days'].' days'))?>" value="<?=e($o['event_date'])?>"></label><label>Heure de livraison<input type="time" name="delivery_time" required value="<?=e(substr($o['delivery_time'],0,5))?>"></label></div>
<div class="two"><label>Adresse de livraison<input name="delivery_address" required maxlength="255" value="<?=e($o['delivery_address'])?>"></label><label>Ville<input name="delivery_city" required maxlength="100" value="<?=e($o['delivery_city'])?>"></label></div>
<div class="two"><label>Distance estimée en km (hors Bordeaux)<input type="number" name="distance_km" min="0.01" max="1000" step="0.01" value="<?=e((string)($o['distance_km']??''))?>"></label><label>Personnes (minimum <?=(int)$o['min_people']?>)<input type="number" name="people" required min="<?=(int)$o['min_people']?>" max="500" value="<?=(int)$o['people']?>"></label></div>
<button class="btn" type="submit">Enregistrer les modifications</button></form></details>
<form method="post" action="<?=e(url('account'))?>" class="inline"><?=csrf_input()?><input type="hidden" name="action" value="cancel_order"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>"><button class="btn ghost" type="submit">Annuler la commande</button></form>
<?php endif;?>
<?php if($o['status']==='completed'):?><form method="post" action="<?=e(url('account'))?>"><?=csrf_input()?><input type="hidden" name="action" value="review"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>"><label>Note<select name="rating"><option value="5">5/5</option><option value="4">4/5</option><option value="3">3/5</option><option value="2">2/5</option><option value="1">1/5</option></select></label><label>Votre avis<textarea name="comment" required maxlength="2000"></textarea></label><button class="btn">Envoyer l’avis</button></form><?php endif;?>
</article><?php endforeach;?>
<?php elseif($page==='staff'): ?>
<?php $u=require_staff(); ?>
<section class="page-heading"><p class="eyebrow">Back-office</p><h1>Gestion des commandes et du catalogue</h1><p>Rôle connecté : <?=e($u['role'])?></p></section>
<h2>Commandes</h2>
<form method="get" class="filter-panel staff-filter" action="index.php"><input type="hidden" name="page" value="staff">
<label>Statut<select name="status"><option value="">Tous</option><?php $statusFilter=(string)($_GET['status']??'');foreach(['pending','accepted','preparing','shipping','delivered','awaiting_material','completed','cancelled'] as $s):?><option value="<?=e($s)?>" <?=$statusFilter===$s?'selected':''?>><?=e($s)?></option><?php endforeach;?></select></label>
<label>Email du client<input type="search" name="client" maxlength="190" placeholder="Rechercher par email" value="<?=e((string)($_GET['client']??''))?>"></label>
<button class="btn" type="submit">Filtrer</button><a href="<?=e(url('staff'))?>">Réinitialiser</a></form>
<?php
$clientFilter=trim((string)($_GET['client']??''));
$sql='SELECT o.*,m.title,u.email FROM orders o JOIN menus m ON m.id=o.menu_id JOIN users u ON u.id=o.user_id WHERE 1=1';
$params=[];
if(in_array($statusFilter,['pending','accepted','preparing','shipping','delivered','awaiting_material','completed','cancelled'],true)){$sql.=' AND o.status=?';$params[]=$statusFilter;}
if($clientFilter!==''){$sql.=' AND u.email LIKE ?';$params[]='%'.str_replace(['%','_'],['\%','\_'],$clientFilter).'%';}
$sql.=' ORDER BY o.id DESC LIMIT 50';$stmt=db()->prepare($sql);$stmt->execute($params);$staffOrders=$stmt->fetchAll();
if(!$staffOrders):?><p>Aucune commande trouvée.</p><?php endif;?>
<?php foreach($staffOrders as $o):?><article class="order-card"><div class="order-top"><div><strong>#<?=(int)$o['id']?> — <?=e($o['title'])?></strong><p><?=e($o['email'])?> · <?=e($o['event_date'])?> · <?=euro((int)$o['total_cents'])?></p></div><span class="status"><?=e($o['status'])?></span></div>
<?php $trans=['pending'=>['accepted','cancelled'],'accepted'=>['preparing','cancelled'],'preparing'=>['shipping','cancelled'],'shipping'=>['delivered'],'delivered'=>['awaiting_material','completed'],'awaiting_material'=>['completed']];if(!empty($trans[$o['status']])):?>
<form method="post" action="<?=e(url('staff'))?>" class="staff-form"><?=csrf_input()?><input type="hidden" name="action" value="change_status"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>"><label>Nouveau statut<select name="status" required><option value="">Choisir…</option><?php foreach($trans[$o['status']] as $st):?><option value="<?=e($st)?>"><?=e($st)?></option><?php endforeach;?></select></label><label>Contact en cas d’annulation<select name="contact_method"><option value="">—</option><option value="phone">Téléphone</option><option value="email">Email</option></select></label><label>Motif d’annulation<input name="cancellation_reason" maxlength="1000"></label><button class="btn" type="submit">Mettre à jour</button></form>
<?php endif;?></article><?php endforeach;?>
<h2>Catalogue des menus</h2><p>Créer ou modifier les menus. Décochez « Visible » pour retirer un menu du catalogue sans supprimer les commandes existantes. Les prix sont saisis en centimes.</p>
<details class="order-card"><summary><strong>Créer un nouveau menu</strong></summary>
<form method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_menu"><input type="hidden" name="menu_id" value="0">
<div class="two"><label>Titre<input name="title" maxlength="150" required></label><label>Thème<input name="theme" maxlength="45" required></label></div><label>Description<textarea name="description" maxlength="5000" required></textarea></label>
<div class="two"><label>Régime<input name="diet" maxlength="45" required></label><label>Prix en centimes (€ × 100)<input type="number" min="1" name="price_cents" required></label></div>
<div class="two"><label>Personnes minimum<input type="number" min="1" max="500" name="min_people" required></label><label>Stock<input type="number" min="0" name="stock" value="0" required></label></div><label>Préavis en jours<input type="number" name="lead_days" min="0" max="365" value="2" required></label><label>Conditions<textarea name="conditions_text" maxlength="5000" required></textarea></label><label class="check-label"><input type="checkbox" name="active" checked> Visible sur le site</label><button class="btn">Créer le menu</button></form></details>
<?php foreach(db()->query('SELECT * FROM menus ORDER BY id DESC') as $m):?>
<details class="order-card"><summary><strong><?=e($m['title'])?> · <?=euro((int)$m['price_cents'])?> · <?=((int)$m['active']===1?'Visible':'Masqué')?></strong></summary>
<form method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_menu"><input type="hidden" name="menu_id" value="<?=(int)$m['id']?>">
<div class="two"><label>Titre<input name="title" value="<?=e($m['title'])?>" required maxlength="150"></label><label>Thème<input name="theme" value="<?=e($m['theme'])?>" required maxlength="45"></label></div>
<label>Description<textarea name="description" maxlength="5000" required><?=e($m['description'])?></textarea></label>
<div class="two"><label>Régime<input name="diet" value="<?=e($m['diet'])?>" required maxlength="45"></label><label>Prix en centimes<input type="number" name="price_cents" min="1" value="<?=(int)$m['price_cents']?>" required></label></div>
<div class="two"><label>Personnes minimum<input type="number" name="min_people" min="1" max="500" value="<?=(int)$m['min_people']?>" required></label><label>Stock<input type="number" name="stock" min="0" value="<?=(int)$m['stock']?>" required></label></div>
<label>Préavis en jours<input type="number" name="lead_days" min="0" max="365" value="<?=(int)$m['lead_days']?>" required></label><label>Conditions<textarea name="conditions_text" maxlength="5000" required><?=e($m['conditions_text'])?></textarea></label>
<label class="check-label"><input type="checkbox" name="active" <?=(int)$m['active']?'checked':''?>> Visible sur le site</label><button type="submit" class="btn">Enregistrer le menu</button></form>
<h3>Plats associés</h3>
<?php $linked=db()->prepare('SELECT d.id,d.name FROM dishes d JOIN menu_dishes md ON md.dish_id=d.id WHERE md.menu_id=?');$linked->execute([$m['id']]);foreach($linked as $d):?><form class="inline" method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="unlink_dish"><input type="hidden" name="menu_id" value="<?=(int)$m['id']?>"><input type="hidden" name="dish_id" value="<?=(int)$d['id']?>"><span><?=e($d['name'])?></span> <button class="btn ghost" type="submit">Retirer</button></form> <?php endforeach;?>
<form method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="link_dish"><input type="hidden" name="menu_id" value="<?=(int)$m['id']?>"><label>Ajouter un plat existant<select name="dish_id" required><?php foreach(db()->query('SELECT id,name FROM dishes ORDER BY name') as $d):?><option value="<?=(int)$d['id']?>"><?=e($d['name'])?></option><?php endforeach;?></select></label><button class="btn ghost">Associer ce plat</button></form></details>
<?php endforeach;?>
<h2>Gérer les plats</h2>
<details class="order-card"><summary><strong>Créer un plat</strong></summary><form method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_dish"><input type="hidden" name="dish_id" value="0"><label>Nom<input name="name" required maxlength="160"></label><label>Catégorie<select name="kind"><option value="entree">Entrée</option><option value="plat">Plat</option><option value="dessert">Dessert</option></select></label><label>Allergènes<input name="allergens" maxlength="255" placeholder="Ex. : lait, œufs"></label><button class="btn">Créer le plat</button></form></details>
<?php foreach(db()->query('SELECT * FROM dishes ORDER BY name') as $d):?><details class="order-card"><summary><?=e($d['name'])?> (<?=e($d['kind'])?>)</summary><form method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_dish"><input type="hidden" name="dish_id" value="<?=(int)$d['id']?>"><label>Nom<input name="name" required maxlength="160" value="<?=e($d['name'])?>"></label><label>Catégorie<select name="kind"><?php foreach(['entree'=>'Entrée','plat'=>'Plat','dessert'=>'Dessert'] as $k=>$label):?><option value="<?=e($k)?>" <?=$d['kind']===$k?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label><label>Allergènes<input name="allergens" maxlength="255" value="<?=e($d['allergens'])?>"></label><button class="btn ghost">Enregistrer le plat</button></form></details><?php endforeach;?>
<h2>Horaires d'ouverture</h2>
<div class="cards"><?php foreach($hours as $h):?><form class="order-card" method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="save_hours"><input type="hidden" name="day_no" value="<?=(int)$h['day_no']?>"><label><?=e($h['label_fr'])?><input name="hours" required maxlength="60" value="<?=e($h['hours'])?>"></label><button class="btn ghost">Enregistrer</button></form><?php endforeach;?></div>
<h2>Avis à modérer</h2><?php foreach(db()->query('SELECT * FROM reviews WHERE status="pending" ORDER BY id DESC') as $r):?><article class="order-card"><strong><?=str_repeat('★',(int)$r['rating'])?></strong><p><?=e($r['comment'])?></p><form method="post" action="<?=e(url('staff'))?>"><?=csrf_input()?><input type="hidden" name="action" value="moderate_review"><input type="hidden" name="review_id" value="<?=(int)$r['id']?>"><label>Décision<select name="status"><option value="approved">Approuver</option><option value="rejected">Refuser</option></select></label><button class="btn">Appliquer</button></form></article><?php endforeach;?>
<?php if($u['role']==='admin'):?>
<h2>Créer un employé</h2><form method="post" action="<?=e(url('staff'))?>" class="form-card compact"><?=csrf_input()?><input type="hidden" name="action" value="create_employee"><div class="two"><label>Prénom<input name="first_name" required></label><label>Nom<input name="last_name" required></label></div><label>Email<input type="email" name="email" required></label><label>Téléphone<input name="phone" required></label><label>Mot de passe fort<input type="password" name="password" required minlength="10"></label><button class="btn">Créer un employé</button></form>
<h2>Désactiver un employé</h2><?php foreach(db()->query('SELECT id,email FROM users WHERE role="employee" AND active=1') as $emp):?><form method="post" action="<?=e(url('staff'))?>" class="inline"><?=csrf_input()?><input type="hidden" name="action" value="disable_employee"><input type="hidden" name="employee_id" value="<?=(int)$emp['id']?>"><span><?=e($emp['email'])?></span> <button class="btn ghost">Désactiver</button></form><?php endforeach;?>
<div class="notice">Statistiques MongoDB et graphiques : non intégrés à cette version. L'énoncé impose une base non relationnelle réelle.</div>
<?php endif; ?>
<?php elseif($page==='contact'): ?>
<section class="page-heading"><h1>Nous contacter</h1><p>Une question sur nos menus ou une prestation ?</p></section><div class="auth-wrapper wide"><form method="post" action="<?=e(url('contact'))?>" class="form-card"><?=csrf_input()?><input type="hidden" name="action" value="contact"><label>Objet<input name="subject" required maxlength="160"></label><label>Votre email<input name="email" type="email" required maxlength="190"></label><label>Votre message<textarea name="body" required maxlength="5000" rows="6"></textarea></label><button class="btn" type="submit">Enregistrer le message</button><small>Envoi d’email à l’entreprise non connecté dans cette version.</small></form></div>
<?php elseif($page==='legal'): ?><section class="page-heading"><h1>Mentions légales</h1></section><p><strong>Document à compléter avant la mise en ligne.</strong> Identité, coordonnées et hébergeur doivent être vérifiés auprès de l’entreprise fictive.</p>
<?php elseif($page==='terms'): ?><section class="page-heading"><h1>Conditions générales de vente</h1></section><p><strong>Projet pédagogique — conditions juridiques à finaliser avant mise en production.</strong></p><p>La commande est soumise au minimum de convives et aux délais indiqués sur la fiche du menu. Hors Bordeaux, la livraison est facturée 5 € plus 0,59 € par kilomètre parcouru. Une remise de 10 % s'applique dès 5 convives supplémentaires. En cas de prêt de matériel, les conditions de restitution et les éventuels frais de 600 € après 10 jours ouvrés devront être confirmés et intégrés aux CGV définitives.</p>
<?php else: ?><h1>Page introuvable</h1><?php endif; ?>
</main><footer class="site-footer"><div class="container footer-grid"><div><h2>Vite & Gourmand</h2><p>Traiteur à Bordeaux depuis 25 ans.</p><a href="<?=e(url('contact'))?>">Nous contacter</a></div><div><h3>Horaires</h3><ul class="hours"><?php foreach($hours as $h):?><li><span><?=e($h['label_fr'])?></span><span><?=e($h['hours'])?></span></li><?php endforeach;?></ul></div><div><h3>Informations</h3><p><a href="<?=e(url('legal'))?>">Mentions légales</a></p><p><a href="<?=e(url('terms'))?>">Conditions générales de vente</a></p></div></div><div class="footer-bottom">© <?=date('Y')?> Vite & Gourmand · Projet pédagogique Studi</div></footer>
</body></html>
