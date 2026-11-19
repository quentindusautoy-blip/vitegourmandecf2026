

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(25) NOT NULL,
  address VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','employee','admin') NOT NULL DEFAULT 'user',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  theme VARCHAR(45) NOT NULL,
  diet VARCHAR(45) NOT NULL,
  min_people INT UNSIGNED NOT NULL,
  price_cents INT UNSIGNED NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  lead_days INT UNSIGNED NOT NULL DEFAULT 2,
  conditions_text TEXT NOT NULL,
  image_url VARCHAR(400) DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  CHECK (min_people > 0),
  CHECK (price_cents > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dishes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 kind ENUM('entree','plat','dessert') NOT NULL,
 allergens VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_dishes (
 menu_id INT UNSIGNED NOT NULL,
 dish_id INT UNSIGNED NOT NULL,
 PRIMARY KEY (menu_id, dish_id),
 FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
 FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 menu_id INT UNSIGNED NOT NULL,
 people INT UNSIGNED NOT NULL,
 event_date DATE NOT NULL,
 delivery_time TIME NOT NULL,
 delivery_address VARCHAR(255) NOT NULL,
 delivery_city VARCHAR(100) NOT NULL,
 distance_km DECIMAL(8,2) DEFAULT NULL,
 subtotal_cents INT UNSIGNED NOT NULL,
 discount_cents INT UNSIGNED NOT NULL DEFAULT 0,
 shipping_cents INT UNSIGNED NOT NULL DEFAULT 0,
 total_cents INT UNSIGNED NOT NULL,
 status ENUM('pending','accepted','preparing','shipping','delivered','awaiting_material','completed','cancelled') NOT NULL DEFAULT 'pending',
 cancellation_reason TEXT DEFAULT NULL,
 contact_method ENUM('phone','email') DEFAULT NULL,
 material_loaned TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id),
 FOREIGN KEY (menu_id) REFERENCES menus(id),
 INDEX idx_orders_user (user_id),
 INDEX idx_orders_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_history (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 status VARCHAR(40) NOT NULL,
 changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 changed_by INT UNSIGNED DEFAULT NULL,
 FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reviews (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL UNIQUE,
 user_id INT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 comment TEXT NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (order_id) REFERENCES orders(id),
 FOREIGN KEY (user_id) REFERENCES users(id),
 CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 subject VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL,
 body TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS opening_hours (
 day_no TINYINT UNSIGNED PRIMARY KEY,
 label_fr VARCHAR(20) NOT NULL,
 hours VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

INSERT INTO opening_hours (day_no,label_fr,hours) VALUES
(1,'Lundi','09h00 – 18h00'),(2,'Mardi','09h00 – 18h00'),(3,'Mercredi','09h00 – 18h00'),
(4,'Jeudi','09h00 – 18h00'),(5,'Vendredi','09h00 – 18h00'),(6,'Samedi','09h00 – 17h00'),(7,'Dimanche','Fermé')
ON DUPLICATE KEY UPDATE label_fr=VALUES(label_fr), hours=VALUES(hours);

INSERT INTO menus (id,title,description,theme,diet,min_people,price_cents,stock,lead_days,conditions_text,image_url) VALUES
(1,'Table de Noël','Un repas festif pour réunir famille et amis autour de produits gourmands.','Noël','Classique',6,24000,12,5,'Commander au moins 5 jours avant la prestation. Conservation au frais jusqu’au service.',NULL),
(2,'Jardin végétarien','Un menu généreux qui met les légumes de saison à l’honneur.','Événement','Végétarien',4,13200,20,3,'Commander au moins 3 jours avant. Les plats doivent être conservés à 4 °C.',NULL),
(3,'Brunch de fête','Viennoiseries, produits frais et douceurs pour un moment convivial.','Classique','Classique',8,19200,15,2,'Commander au moins 48 heures avant. Livraison suivant la disponibilité.',NULL),
(4,'Pâques en famille','Des saveurs printanières pour célébrer Pâques en famille.','Pâques','Classique',5,17500,8,4,'Réservation au moins 4 jours avant. À conserver au frais.',NULL)
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO dishes (id,name,kind,allergens) VALUES
(1,'Velouté de courge','entree','Lait'),(2,'Saumon fumé citronné','entree','Poisson'),
(3,'Volaille rôtie et légumes','plat',NULL),(4,'Bûche chocolat','dessert','Gluten, Œufs, Lait'),
(5,'Tartare de betterave','entree',NULL),(6,'Risotto de champignons','plat','Lait'),
(7,'Salade de fruits','dessert',NULL),(8,'Œufs brouillés','plat','Œufs, Lait'),
(9,'Carrot cake','dessert','Gluten, Œufs, Fruits à coque'),(10,'Agneau confit','plat',NULL)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT IGNORE INTO menu_dishes(menu_id,dish_id) VALUES
(1,2),(1,3),(1,4),(2,5),(2,6),(2,7),(3,1),(3,8),(3,9),(4,1),(4,10),(4,7);
