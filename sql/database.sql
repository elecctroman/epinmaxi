SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(32) NULL,
  password_hash VARCHAR(255) NOT NULL,
  twofa_secret VARCHAR(64) NULL,
  permissions_json JSON NULL,
  status ENUM('active','banned') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  sort INT DEFAULT 0,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('epin','license','account') NOT NULL,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  sku VARCHAR(120) NOT NULL UNIQUE,
  category_id INT UNSIGNED NULL,
  price DECIMAL(12,2) NOT NULL,
  sale_price DECIMAL(12,2) NULL,
  currency CHAR(3) NOT NULL DEFAULT 'TRY',
  stock_policy ENUM('track_keys','unlimited') NOT NULL DEFAULT 'track_keys',
  description TEXT,
  tags JSON NULL,
  highlights JSON NULL,
  faq JSON NULL,
  gallery JSON NULL,
  cover_image VARCHAR(255) NULL,
  status ENUM('active','draft','inactive') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_products_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_keys (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  code VARBINARY(255) NOT NULL,
  meta_json JSON NULL,
  status ENUM('unused','used','refunded') NOT NULL DEFAULT 'unused',
  order_item_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_keys_product (product_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  username VARBINARY(255) NOT NULL,
  password_encrypted VARBINARY(255) NOT NULL,
  meta_json JSON NULL,
  status ENUM('unused','used','refunded') NOT NULL DEFAULT 'unused',
  order_item_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_accounts_product (product_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  type ENUM('percent','fixed') NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  max_uses INT DEFAULT 0,
  used_count INT DEFAULT 0,
  min_subtotal DECIMAL(12,2) DEFAULT 0,
  max_discount DECIMAL(12,2) DEFAULT 0,
  per_user_limit INT DEFAULT 0,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_cart_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(32) NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'TRY',
  payment_method VARCHAR(60) NOT NULL,
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  status ENUM('new','processing','completed','cancelled') NOT NULL DEFAULT 'new',
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  coupon_code VARCHAR(80) NULL,
  coupon_discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  paid_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_coupon (coupon_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  delivery_payload JSON NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  provider VARCHAR(60) NOT NULL,
  provider_txn_id VARCHAR(120) NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL,
  status ENUM('success','failed','pending') NOT NULL,
  raw_response_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wallets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wallet_id INT UNSIGNED NOT NULL,
  type ENUM('credit','debit') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL,
  status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  message TEXT NOT NULL,
  attachments_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(120) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NULL,
  entity_id INT UNSIGNED NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  details JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  email VARCHAR(160) NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  status ENUM('success','failed') NOT NULL DEFAULT 'failed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_login_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blocked_ips (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(255) NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhook_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(80) NOT NULL,
  reference VARCHAR(120) NULL,
  payload JSON NULL,
  signature_valid TINYINT(1) NOT NULL DEFAULT 0,
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_webhook_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS failed_logins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_failed_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  token VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_resets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_verifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
INSERT INTO categories (name, slug, sort) VALUES
('Oyun Kodları', 'oyun-kodlari', 1),
('Abonelikler', 'abonelikler', 2),
('Yazılım Lisansları', 'yazilim-lisanslari', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort = VALUES(sort);

INSERT INTO products (type,title,slug,sku,category_id,price,sale_price,currency,stock_policy,description,tags,highlights,faq,gallery,cover_image,status)
VALUES
('epin','Valorant VP 1250','valorant-vp-1250','VALO-1250',1,199.90,NULL,'TRY','track_keys','Valorant hesabınızı güçlendirmek için 1250 VP kodu.',
 JSON_ARRAY('popüler','aninda-teslim'),
 JSON_ARRAY('Anında teslimat','Tüm bölgelerde geçerli','Sınırlı stok'),
 JSON_ARRAY(JSON_OBJECT('question','Kodu nasıl kullanırım?','answer','Riot hesabınıza giriş yapıp mağaza > kodu kullan bölümünden girebilirsiniz.')),
 JSON_ARRAY('/assets/img/valorant.png','/assets/img/valorant-alt.png'),
 '/assets/img/valorant.png','active'),
('license','Microsoft 365 Bireysel','microsoft-365-bireysel','MS365-IND',3,749.90,699.90,'TRY','track_keys','1 yıllık Microsoft 365 bireysel aboneliği.',
 JSON_ARRAY('isyeri','lisans'),
 JSON_ARRAY('1 yıllık lisans','5 cihaza kadar kullanım','OneDrive 1TB depolama'),
 JSON_ARRAY(JSON_OBJECT('question','Yenileme gerekiyor mu?','answer','Evet, 12 ay sonunda yeniden lisans almanız gerekir.')),
 JSON_ARRAY('/assets/img/m365.png'),
 '/assets/img/m365.png','active'),
('account','Spotify Premium 3 Ay','spotify-premium-3ay','SPOT-3M',2,149.90,NULL,'TRY','track_keys','Spotify Premium 3 aylık aile planı hesabı.',
 JSON_ARRAY('muzik','abonelik'),
 JSON_ARRAY('Yüksek kalite müzik','Aile paylaşımı','Anında hesap teslimi'),
 JSON_ARRAY(JSON_OBJECT('question','Hesabı nasıl devralacağım?','answer','Sipariş tamamlandığında giriş bilgileri panelinizde görünür.')),
 JSON_ARRAY('/assets/img/spotify.png'),
 '/assets/img/spotify.png','active')
ON DUPLICATE KEY UPDATE price=VALUES(price), sale_price=VALUES(sale_price), status=VALUES(status);

INSERT INTO product_keys (product_id, code, status)
SELECT p.id, payload.code, 'unused'
FROM products p
JOIN (
    SELECT 'FCE4QgfGACri//pkkKZPaERk7avmJ4/jEy5LC8dxKM3G43swrw==' AS code UNION ALL
    SELECT 'cSWaAb+s6CqPnucI8awq0SMOvB8h3CE/nHIs+mH2BVrcs4H4Ng==' UNION ALL
    SELECT 'M+NeVtCc56ejAtbN4ngalPOUq6yqrSwxd13u4dReLH+CIaObZg==' UNION ALL
    SELECT '9U0FsuXPD5G74Shqs+THUArISAG7WivkH1zeSS9JbMNS9z6Q6Q==' UNION ALL
    SELECT 'ZksZFS1TMnRlcShmMckZCC6MnMrYg7vnDieiZKMZKtDEOsEK2w=='
) AS payload
WHERE p.slug = 'valorant-vp-1250';

INSERT INTO product_accounts (product_id, username, password_encrypted, status)
SELECT p.id, payload.username, payload.password, 'unused'
FROM products p
JOIN (
    SELECT 'tnI4u1aTRsN1JfI4GCbKBjyZFYFTTdEpggdDOt0TmAUyP8Lz' AS username, 'iXbHrO0qA088dqe+G+xYmz3/jEJJ3rY2iM1q9/cbBTNo78KGogM=' AS password
    UNION ALL SELECT 's9beRPeqnjELHpBpf8mJu1AeHfVBQr9o1EfkXi/fRL5YJq5X', '+zztrLEA2elowM/AG8C2BnjBljLoNvO/FTXhpWrO65KW4oBS5SQ='
    UNION ALL SELECT 'BUHlXO4tUCMiNN4BvpCMTty9HXPGc3wp7U1JaGmhapE9Ly+G', 'AatrDfphaVywONoppWHG7eUof3iHg0cOI1IUeQ94H+QUch2Dfdc='
) AS payload
WHERE p.slug = 'spotify-premium-3ay';

INSERT INTO coupons (code,type,value,max_uses,used_count,min_subtotal,max_discount,per_user_limit,starts_at,ends_at,status) VALUES
('HOSGELDIN', 'percent', 10, 100, 0, 200, 150, 1, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'active')
ON DUPLICATE KEY UPDATE value=VALUES(value), ends_at=VALUES(ends_at);

INSERT INTO settings (`key`,`value`) VALUES
('app.name','E-PIN Premium'),
('app.currency','TRY'),
('app.timezone','Europe/Istanbul'),
('app.encryption_key','demo-enc-key-32chars!!demo'),
('app.theme.primary','#6366f1'),
('app.theme.secondary','#10b981'),
('app.theme.mode','light'),
('mail.from','no-reply@demo.local'),
('payment.default','mock'),
('seo.meta_title','E-PIN Premium Dijital Mağaza'),
('seo.meta_description','Oyun kodları, lisanslar ve dijital aboneliklerde anında teslimat deneyimi.'),
('api.token_subject',''),
('sms.enabled','0')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO users (role,name,email,phone,password_hash,twofa_secret,permissions_json,status,created_at)
VALUES
('staff','Destek Personeli','staff@demo.local',NULL,'$2y$12$BzWFuo16wGqvXTtfndpjgO3/e9x.xhnoLdMHCxJq3YeWbAydhk/XS',NULL,JSON_ARRAY('manage-support','manage-orders','manage-keys'),'active',NOW())
ON DUPLICATE KEY UPDATE status=VALUES(status), permissions_json=VALUES(permissions_json);
