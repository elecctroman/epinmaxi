INSERT INTO categories (name, slug, sort) VALUES
('Oyun Kodları', 'oyun-kodlari', 1),
('Abonelikler', 'abonelikler', 2),
('Yazılım Lisansları', 'yazilim-lisanslari', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort = VALUES(sort);

INSERT INTO products (type,title,slug,sku,category_id,price,sale_price,currency,stock_policy,description,cover_image,status)
VALUES
('epin','Valorant VP 1250','valorant-vp-1250','VALO-1250',1,199.90,NULL,'TRY','track_keys','Valorant hesabınızı güçlendirmek için 1250 VP kodu.','/assets/img/valorant.png','active'),
('license','Microsoft 365 Bireysel','microsoft-365-bireysel','MS365-IND',3,749.90,699.90,'TRY','track_keys','1 yıllık Microsoft 365 bireysel aboneliği.','/assets/img/m365.png','active'),
('account','Spotify Premium 3 Ay','spotify-premium-3ay','SPOT-3M',2,149.90,NULL,'TRY','track_keys','Spotify Premium 3 aylık aile planı hesabı.','/assets/img/spotify.png','active')
ON DUPLICATE KEY UPDATE price=VALUES(price), sale_price=VALUES(sale_price), status=VALUES(status);

INSERT INTO product_keys (product_id, code, status)
SELECT p.id, AES_ENCRYPT(CONCAT('VALO-', LPAD(n,4,'0')),'demo-secret'), 'unused'
FROM products p
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) AS seq
WHERE p.slug = 'valorant-vp-1250'
ON DUPLICATE KEY UPDATE status='unused';

INSERT INTO product_accounts (product_id, username, password_encrypted, status)
SELECT p.id, AES_ENCRYPT(CONCAT('spotify', n),'demo-secret'), AES_ENCRYPT('Passw0rd!','demo-secret'), 'unused'
FROM products p
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3) AS seq
WHERE p.slug = 'spotify-premium-3ay'
ON DUPLICATE KEY UPDATE status='unused';

INSERT INTO coupons (code,type,value,max_uses,used_count,min_subtotal,starts_at,ends_at,status) VALUES
('HOSGELDIN', 'percent', 10, 100, 0, 200, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'active')
ON DUPLICATE KEY UPDATE value=VALUES(value), ends_at=VALUES(ends_at);

INSERT INTO settings (`key`,`value`) VALUES
('app.name','E-PIN Premium'),
('app.currency','TRY'),
('app.timezone','Europe/Istanbul')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
