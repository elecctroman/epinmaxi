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
