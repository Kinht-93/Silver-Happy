
USE silverhappy;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE provider_payments;
TRUNCATE TABLE provider_invoices;
TRUNCATE TABLE provider_missions;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE products;
TRUNCATE TABLE event_registrations;
TRUNCATE TABLE events;
TRUNCATE TABLE notifications;
TRUNCATE TABLE contents;
TRUNCATE TABLE reviews;
TRUNCATE TABLE invoices;
TRUNCATE TABLE quotes;
TRUNCATE TABLE completed_services;
TRUNCATE TABLE show_type;
TRUNCATE TABLE service_requests;
TRUNCATE TABLE service_types;
TRUNCATE TABLE service_categories;
TRUNCATE TABLE subscribed;
TRUNCATE TABLE subscription_types;
TRUNCATE TABLE contracts;
TRUNCATE TABLE provider_availabilities;
TRUNCATE TABLE availability;
TRUNCATE TABLE senior_settings;
TRUNCATE TABLE active_users;
TRUNCATE TABLE messages;
TRUNCATE TABLE medical_appointments;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO subscription_types VALUES
('sub1','Essentiel','senior',9.90,99.00),
('sub2','Confort','senior',19.90,199.00),
('sub3','Premium','senior',34.90,349.00),
('sub4','Prestataire Standard','prestataire',0.00,0.00);

INSERT INTO users (id_user,email,password,role,last_name,first_name,phone,city,postal_code,active,verified_email,created_at) VALUES
('admin1','admin@silverhappy.fr','$2y$10$xxx','admin','Dupont','Claire','0601010101','Paris','75001',TRUE,TRUE,'2023-01-10 09:00:00');

INSERT INTO users (id_user,email,password,role,last_name,first_name,phone,city,postal_code,birth_date,active,verified_email,created_at,siret_number,company_name,validation_status,average_rating,commission_rate,zone) VALUES
('prest1','marc.leblanc@mail.fr','$2y$10$xxx','prestataire','Leblanc','Marc','0611111111','Paris','75008','1985-03-15',TRUE,TRUE,'2023-02-01 10:00:00','12345678900011','Leblanc Services','validé',4.5,10.00,'Paris, Île-de-France'),
('prest2','julie.martin@mail.fr','$2y$10$xxx','prestataire','Martin','Julie','0622222222','Lyon','69002','1990-07-22',TRUE,TRUE,'2023-02-15 10:00:00','98765432100022','Martin Care','validé',4.8,8.00,'Lyon, Rhône-Alpes'),
('prest3','pierre.duval@mail.fr','$2y$10$xxx','prestataire','Duval','Pierre','0633333333','Marseille','13001','1978-11-05',TRUE,TRUE,'2023-03-01 10:00:00','11122233300033','Duval Aide','en attente',0.0,12.00,'Marseille, PACA'),
('prest4','sophie.moreau@mail.fr','$2y$10$xxx','prestataire','Moreau','Sophie','0644444444','Bordeaux','33000','1992-05-30',TRUE,TRUE,'2023-04-10 10:00:00','44455566600044','Moreau Soins','validé',4.2,10.00,'Bordeaux, Aquitaine');

INSERT INTO users (id_user,email,password,role,last_name,first_name,phone,city,postal_code,birth_date,active,verified_email,tutorial_seen,created_at,membership_number,subscription_date,mobility) VALUES
('senior1','jean.bernard@mail.fr','$2y$10$xxx','senior','Bernard','Jean','0651111111','Paris','75008','1944-06-10',TRUE,TRUE,TRUE,'2023-03-01 09:00:00','MEM001','2023-03-01','Autonome'),
('senior2','marie.petit@mail.fr','$2y$10$xxx','senior','Petit','Marie','0652222222','Paris','75015','1938-11-25',TRUE,TRUE,TRUE,'2023-03-15 09:00:00','MEM002','2023-03-15','Réduite'),
('senior3','robert.garcia@mail.fr','$2y$10$xxx','senior','Garcia','Robert','0653333333','Lyon','69003','1942-02-14',TRUE,TRUE,TRUE,'2023-04-01 09:00:00','MEM003','2023-04-01','Autonome'),
('senior4','yvette.thomas@mail.fr','$2y$10$xxx','senior','Thomas','Yvette','0654444444','Marseille','13005','1936-09-03',FALSE,TRUE,TRUE,'2023-04-20 09:00:00','MEM004','2023-04-20','Dépendante'),
('senior5','pierre.lambert@mail.fr','$2y$10$xxx','senior','Lambert','Pierre','0655555555','Lyon','69001','1950-01-18',TRUE,TRUE,TRUE,'2023-05-10 09:00:00','MEM005','2023-05-10','Autonome'),
('senior6','helene.roux@mail.fr','$2y$10$xxx','senior','Roux','Hélène','0656666666','Bordeaux','33000','1947-07-07',TRUE,TRUE,FALSE,'2023-06-01 09:00:00','MEM006','2023-06-01','Réduite'),
('senior7','claude.simon@mail.fr','$2y$10$xxx','senior','Simon','Claude','0657777777','Paris','75011','1940-12-22',TRUE,TRUE,TRUE,'2023-07-15 09:00:00','MEM007','2023-07-15','Autonome'),
('senior8','monique.dupuis@mail.fr','$2y$10$xxx','senior','Dupuis','Monique','0658888888','Nantes','44000','1945-04-30',TRUE,TRUE,TRUE,'2023-09-01 09:00:00','MEM008','2023-09-01','Réduite'),
('senior9','andre.perrin@mail.fr','$2y$10$xxx','senior','Perrin','André','0659999999','Bordeaux','33100','1952-08-15',TRUE,TRUE,TRUE,'2023-10-01 09:00:00','MEM009','2023-10-01','Autonome'),
('senior10','francoise.morel@mail.fr','$2y$10$xxx','senior','Morel','Françoise','0650000000','Paris','75014','1939-03-11',TRUE,TRUE,TRUE,'2024-01-10 09:00:00','MEM010','2024-01-10','Dépendante'),
('senior11','louis.henry@mail.fr','$2y$10$xxx','senior','Henry','Louis','0651234567','Marseille','13008','1946-05-20',TRUE,TRUE,TRUE,'2024-02-01 09:00:00','MEM011','2024-02-01','Autonome'),
('senior12','colette.blanc@mail.fr','$2y$10$xxx','senior','Blanc','Colette','0657654321','Lyon','69006','1943-10-08',FALSE,TRUE,TRUE,'2024-03-15 09:00:00','MEM012','2024-03-15','Réduite');

INSERT INTO senior_settings VALUES
('senior1','fr','Normale',TRUE,NULL,'2023-03-01 09:00:00'),
('senior2','fr','Grande',TRUE,'Fille','2023-03-15 09:00:00'),
('senior3','fr','Normale',FALSE,NULL,'2023-04-01 09:00:00'),
('senior4','fr','Très grande',TRUE,'Fils','2023-04-20 09:00:00'),
('senior5','fr','Normale',TRUE,NULL,'2023-05-10 09:00:00'),
('senior6','fr','Grande',TRUE,'Neveu','2023-06-01 09:00:00'),
('senior7','fr','Normale',TRUE,NULL,'2023-07-15 09:00:00'),
('senior8','fr','Normale',FALSE,NULL,'2023-09-01 09:00:00'),
('senior9','fr','Grande',TRUE,NULL,'2023-10-01 09:00:00'),
('senior10','fr','Très grande',TRUE,'Fille','2024-01-10 09:00:00'),
('senior11','fr','Normale',TRUE,NULL,'2024-02-01 09:00:00'),
('senior12','fr','Grande',TRUE,'Frère','2024-03-15 09:00:00');

INSERT INTO subscribed VALUES
('senior1','sub2'),('senior2','sub1'),('senior3','sub2'),('senior4','sub3'),
('senior5','sub1'),('senior6','sub2'),('senior7','sub2'),('senior8','sub1'),
('senior9','sub2'),('senior10','sub3'),('senior11','sub1'),('senior12','sub2'),
('prest1','sub4'),('prest2','sub4'),('prest4','sub4');

INSERT INTO service_categories VALUES
('cat1','Aide à domicile','Assistance quotidienne à domicile'),
('cat2','Soins infirmiers','Soins médicaux à domicile'),
('cat3','Transport accompagné','Accompagnement et transport'),
('cat4','Repas à domicile','Préparation et livraison de repas'),
('cat5','Jardinage','Entretien jardin et extérieurs');

INSERT INTO service_types VALUES
('st1','Ménage et repassage','Nettoyage, repassage',18.00,FALSE,'cat1'),
('st2','Aide à la toilette','Aide hygiène quotidienne',25.00,TRUE,'cat1'),
('st3','Pansements et injections','Soins infirmiers courants',35.00,TRUE,'cat2'),
('st4','Accompagnement médical','Transport médecin/hôpital',22.00,FALSE,'cat3'),
('st5','Livraison repas','Repas cuisinés à domicile',15.00,FALSE,'cat4'),
('st6','Tonte et taille','Entretien jardin',20.00,FALSE,'cat5');

INSERT INTO service_requests VALUES
('req1','2024-02-10','09:00:00',120,'12 rue de Rivoli, Paris','completed','2024-02-01 10:00:00','senior1','cat1'),
('req2','2024-02-15','14:00:00',60,'15 av. Victor Hugo, Paris','completed','2024-02-05 11:00:00','senior2','cat2'),
('req3','2024-03-01','10:00:00',90,'8 place Bellecour, Lyon','completed','2024-02-20 09:00:00','senior3','cat3'),
('req4','2024-03-10','11:00:00',60,'3 bd Michelet, Marseille','cancelled','2024-03-01 08:00:00','senior4','cat4'),
('req5','2024-03-20','09:30:00',180,'22 rue Sainte-Catherine, Bordeaux','completed','2024-03-10 10:00:00','senior6','cat5'),
('req6','2024-04-05','10:00:00',120,'5 rue Oberkampf, Paris','completed','2024-03-25 14:00:00','senior7','cat1'),
('req7','2024-04-15','15:00:00',60,'18 quai des Chartrons, Bordeaux','pending','2024-04-08 09:00:00','senior9','cat2'),
('req8','2024-04-20','09:00:00',90,'7 rue Crébillon, Nantes','completed','2024-04-10 11:00:00','senior8','cat3'),
('req9','2024-05-02','14:00:00',120,'1 av. de la Paix, Paris','completed','2024-04-22 10:00:00','senior10','cat1'),
('req10','2024-05-10','10:30:00',60,'30 cours Mirabeau, Marseille','completed','2024-04-30 09:00:00','senior11','cat2'),
('req11','2024-05-15','09:00:00',180,'14 rue Garibaldi, Lyon','completed','2024-05-05 08:00:00','senior5','cat5'),
('req12','2024-06-01','11:00:00',60,'9 rue de la République, Lyon','pending','2024-05-20 10:00:00','senior3','cat4'),
('req13','2024-06-10','10:00:00',90,'12 rue de Rivoli, Paris','completed','2024-05-30 14:00:00','senior1','cat1'),
('req14','2024-07-01','14:00:00',120,'15 av. Victor Hugo, Paris','completed','2024-06-20 09:00:00','senior2','cat2'),
('req15','2024-07-15','09:00:00',60,'22 rue Sainte-Catherine, Bordeaux','completed','2024-07-05 11:00:00','senior6','cat3'),
('req16','2024-08-05','10:00:00',90,'5 rue Oberkampf, Paris','cancelled','2024-07-25 09:00:00','senior7','cat4'),
('req17','2024-08-20','15:00:00',120,'8 place Bellecour, Lyon','completed','2024-08-10 10:00:00','senior3','cat1'),
('req18','2024-09-01','09:30:00',60,'1 av. de la Paix, Paris','completed','2024-08-22 08:00:00','senior10','cat2'),
('req19','2024-09-15','10:00:00',90,'7 rue Crébillon, Nantes','completed','2024-09-05 11:00:00','senior8','cat5'),
('req20','2024-10-01','11:00:00',120,'30 cours Mirabeau, Marseille','completed','2024-09-20 10:00:00','senior11','cat1'),
('req21','2024-10-15','14:00:00',60,'14 rue Garibaldi, Lyon','completed','2024-10-05 09:00:00','senior5','cat3'),
('req22','2024-11-01','09:00:00',180,'18 quai des Chartrons, Bordeaux','completed','2024-10-20 10:00:00','senior9','cat5'),
('req23','2024-11-15','10:00:00',60,'3 bd Michelet, Marseille','pending','2024-11-05 08:00:00','senior4','cat2'),
('req24','2024-12-01','09:30:00',90,'12 rue de Rivoli, Paris','completed','2024-11-20 11:00:00','senior1','cat3'),
('req25','2024-12-15','14:00:00',120,'9 rue de la République, Lyon','completed','2024-12-05 10:00:00','senior12','cat1');

INSERT INTO show_type VALUES
('st1','req1'),('st3','req2'),('st4','req3'),('st5','req4'),('st6','req5'),
('st1','req6'),('st3','req7'),('st4','req8'),('st1','req9'),('st3','req10'),
('st6','req11'),('st5','req12'),('st1','req13'),('st3','req14'),('st4','req15'),
('st5','req16'),('st1','req17'),('st3','req18'),('st6','req19'),('st1','req20'),
('st4','req21'),('st6','req22'),('st3','req23'),('st4','req24'),('st1','req25');

INSERT INTO quotes (id_quote,quote_number,amount_excl_tax,tax_rate,amount_incl_tax,status,created_at,id_request) VALUES
('qt1','DEV-001',60.00,20.00,72.00,'accepted','2024-02-02 10:00:00','req1'),
('qt2','DEV-002',35.00,20.00,42.00,'accepted','2024-02-06 11:00:00','req2'),
('qt3','DEV-003',33.00,20.00,39.60,'accepted','2024-02-21 09:00:00','req3'),
('qt4','DEV-004',15.00,20.00,18.00,'refused','2024-03-02 08:00:00','req4'),
('qt5','DEV-005',60.00,20.00,72.00,'accepted','2024-03-11 10:00:00','req5'),
('qt6','DEV-006',60.00,20.00,72.00,'accepted','2024-03-26 14:00:00','req6'),
('qt7','DEV-007',35.00,20.00,42.00,'pending','2024-04-09 09:00:00','req7'),
('qt8','DEV-008',33.00,20.00,39.60,'accepted','2024-04-11 11:00:00','req8'),
('qt9','DEV-009',60.00,20.00,72.00,'accepted','2024-04-23 10:00:00','req9'),
('qt10','DEV-010',35.00,20.00,42.00,'accepted','2024-05-01 09:00:00','req10'),
('qt11','DEV-011',60.00,20.00,72.00,'accepted','2024-05-06 08:00:00','req11'),
('qt12','DEV-012',15.00,20.00,18.00,'pending','2024-05-21 10:00:00','req12'),
('qt13','DEV-013',60.00,20.00,72.00,'accepted','2024-05-31 14:00:00','req13'),
('qt14','DEV-014',35.00,20.00,42.00,'accepted','2024-06-21 09:00:00','req14'),
('qt15','DEV-015',33.00,20.00,39.60,'accepted','2024-07-06 11:00:00','req15'),
('qt16','DEV-016',15.00,20.00,18.00,'refused','2024-07-26 09:00:00','req16'),
('qt17','DEV-017',60.00,20.00,72.00,'accepted','2024-08-11 10:00:00','req17'),
('qt18','DEV-018',35.00,20.00,42.00,'accepted','2024-08-23 08:00:00','req18'),
('qt19','DEV-019',60.00,20.00,72.00,'accepted','2024-09-06 11:00:00','req19'),
('qt20','DEV-020',60.00,20.00,72.00,'accepted','2024-09-21 10:00:00','req20'),
('qt21','DEV-021',33.00,20.00,39.60,'accepted','2024-10-06 09:00:00','req21'),
('qt22','DEV-022',60.00,20.00,72.00,'accepted','2024-10-21 10:00:00','req22'),
('qt23','DEV-023',35.00,20.00,42.00,'pending','2024-11-06 08:00:00','req23'),
('qt24','DEV-024',33.00,20.00,39.60,'accepted','2024-11-21 11:00:00','req24'),
('qt25','DEV-025',60.00,20.00,72.00,'accepted','2024-12-06 10:00:00','req25');

INSERT INTO completed_services VALUES
('cs1','2024-02-10','09:00:00','11:00:00',54.00,6.00,'validated','req1'),
('cs2','2024-02-15','14:00:00','15:00:00',31.50,3.50,'validated','req2'),
('cs3','2024-03-01','10:00:00','11:30:00',29.70,3.30,'validated','req3'),
('cs4','2024-03-20','09:30:00','12:30:00',54.00,6.00,'validated','req5'),
('cs5','2024-04-05','10:00:00','12:00:00',54.00,6.00,'validated','req6'),
('cs6','2024-04-20','09:00:00','10:30:00',29.70,3.30,'validated','req8'),
('cs7','2024-05-02','14:00:00','16:00:00',54.00,6.00,'validated','req9'),
('cs8','2024-05-10','10:30:00','11:30:00',31.50,3.50,'validated','req10'),
('cs9','2024-05-15','09:00:00','12:00:00',54.00,6.00,'validated','req11'),
('cs10','2024-06-10','10:00:00','11:30:00',54.00,6.00,'validated','req13'),
('cs11','2024-07-01','14:00:00','16:00:00',31.50,3.50,'validated','req14'),
('cs12','2024-07-15','09:00:00','10:00:00',29.70,3.30,'validated','req15'),
('cs13','2024-08-20','15:00:00','17:00:00',54.00,6.00,'validated','req17'),
('cs14','2024-09-01','09:30:00','10:30:00',31.50,3.50,'validated','req18'),
('cs15','2024-09-15','10:00:00','13:00:00',54.00,6.00,'validated','req19'),
('cs16','2024-10-01','11:00:00','13:00:00',54.00,6.00,'validated','req20'),
('cs17','2024-10-15','14:00:00','15:00:00',29.70,3.30,'validated','req21'),
('cs18','2024-11-01','09:00:00','12:00:00',54.00,6.00,'validated','req22'),
('cs19','2024-12-01','09:30:00','11:00:00',29.70,3.30,'validated','req24'),
('cs20','2024-12-15','14:00:00','16:00:00',54.00,6.00,'validated','req25');

INSERT INTO reviews VALUES
('rev1',4.5,'Très bien, ponctuel et efficace.','2024-02-12 10:00:00',TRUE,'senior1'),
('rev2',5.0,'Excellente prestataire, très professionnelle.','2024-02-17 11:00:00',TRUE,'senior2'),
('rev3',4.0,'Bonne prestation, je recommande.','2024-03-03 09:00:00',TRUE,'senior3'),
('rev4',4.5,'Très satisfait du service.','2024-03-22 10:00:00',TRUE,'senior6'),
('rev5',3.5,'Correct mais peut mieux faire.','2024-04-08 14:00:00',TRUE,'senior7'),
('rev6',5.0,'Parfait, merci !','2024-04-22 09:00:00',TRUE,'senior8'),
('rev7',4.0,'Très bien dans l\'ensemble.','2024-05-05 11:00:00',TRUE,'senior10'),
('rev8',4.5,'Service impeccable.','2024-05-12 10:00:00',TRUE,'senior11'),
('rev9',5.0,'Je recommande vivement.','2024-05-18 09:00:00',TRUE,'senior5');

INSERT INTO invoices VALUES
('inv1','FACT-001','service',60.00,20.00,72.00,'2024-02-10','2024-03-10','paid','qt1'),
('inv2','FACT-002','service',35.00,20.00,42.00,'2024-02-15','2024-03-15','paid','qt2'),
('inv3','FACT-003','service',33.00,20.00,39.60,'2024-03-01','2024-04-01','paid','qt3'),
('inv4','FACT-004','service',60.00,20.00,72.00,'2024-03-20','2024-04-20','paid','qt5'),
('inv5','FACT-005','service',60.00,20.00,72.00,'2024-04-05','2024-05-05','paid','qt6'),
('inv6','FACT-006','service',33.00,20.00,39.60,'2024-04-20','2024-05-20','paid','qt8'),
('inv7','FACT-007','service',60.00,20.00,72.00,'2024-05-02','2024-06-02','paid','qt9'),
('inv8','FACT-008','service',35.00,20.00,42.00,'2024-05-10','2024-06-10','paid','qt10'),
('inv9','FACT-009','service',60.00,20.00,72.00,'2024-05-15','2024-06-15','paid','qt11'),
('inv10','FACT-010','service',60.00,20.00,72.00,'2024-06-10','2024-07-10','paid','qt13'),
('inv11','FACT-011','service',35.00,20.00,42.00,'2024-07-01','2024-08-01','paid','qt14'),
('inv12','FACT-012','service',33.00,20.00,39.60,'2024-07-15','2024-08-15','paid','qt15'),
('inv13','FACT-013','service',60.00,20.00,72.00,'2024-08-20','2024-09-20','paid','qt17'),
('inv14','FACT-014','service',35.00,20.00,42.00,'2024-09-01','2024-10-01','paid','qt18'),
('inv15','FACT-015','service',60.00,20.00,72.00,'2024-09-15','2024-10-15','paid','qt19'),
('inv16','FACT-016','service',60.00,20.00,72.00,'2024-10-01','2024-11-01','paid','qt20'),
('inv17','FACT-017','service',33.00,20.00,39.60,'2024-10-15','2024-11-15','paid','qt21'),
('inv18','FACT-018','service',60.00,20.00,72.00,'2024-11-01','2024-12-01','paid','qt22'),
('inv19','FACT-019','service',33.00,20.00,39.60,'2024-12-01','2025-01-01','pending','qt24'),
('inv20','FACT-020','service',60.00,20.00,72.00,'2024-12-15','2025-01-15','pending','qt25');

INSERT INTO events VALUES
('ev1','Atelier mémoire','atelier','2024-03-15 10:00:00','2024-03-15 12:00:00',15,10.00),
('ev2','Yoga doux','sport','2024-04-01 09:30:00','2024-04-01 11:00:00',12,8.00),
('ev3','Sortie musée Louvre','sortie','2024-04-20 14:00:00','2024-04-20 18:00:00',20,15.00),
('ev4','Conférence nutrition','conférence','2024-05-10 15:00:00','2024-05-10 17:00:00',30,0.00),
('ev5','Atelier numérique','atelier','2024-05-25 10:00:00','2024-05-25 12:00:00',10,5.00),
('ev6','Randonnée douce','sport','2024-06-08 09:00:00','2024-06-08 13:00:00',18,12.00),
('ev7','Tournoi pétanque','sport','2024-07-14 14:00:00','2024-07-14 18:00:00',24,5.00),
('ev8','Atelier jardinage','atelier','2024-08-03 10:00:00','2024-08-03 12:00:00',8,6.00),
('ev9','Conférence santé cardiaque','conférence','2024-09-20 15:00:00','2024-09-20 17:00:00',40,0.00),
('ev10','Sortie théâtre','sortie','2024-10-05 20:00:00','2024-10-05 22:30:00',25,18.00);

INSERT INTO event_registrations VALUES
('er1','2024-03-10 10:00:00','confirmed',TRUE,'senior1','ev1'),
('er2','2024-03-11 11:00:00','confirmed',TRUE,'senior2','ev1'),
('er3','2024-03-12 09:00:00','confirmed',TRUE,'senior7','ev1'),
('er4','2024-03-25 10:00:00','confirmed',TRUE,'senior1','ev2'),
('er5','2024-03-26 14:00:00','confirmed',TRUE,'senior3','ev2'),
('er6','2024-03-26 15:00:00','confirmed',TRUE,'senior5','ev2'),
('er7','2024-04-10 09:00:00','confirmed',TRUE,'senior2','ev3'),
('er8','2024-04-11 10:00:00','confirmed',TRUE,'senior7','ev3'),
('er9','2024-04-12 11:00:00','confirmed',TRUE,'senior10','ev3'),
('er10','2024-04-13 09:00:00','confirmed',TRUE,'senior1','ev3'),
('er11','2024-05-01 10:00:00','confirmed',FALSE,'senior3','ev4'),
('er12','2024-05-02 11:00:00','confirmed',FALSE,'senior5','ev4'),
('er13','2024-05-02 14:00:00','confirmed',FALSE,'senior6','ev4'),
('er14','2024-05-03 09:00:00','confirmed',FALSE,'senior8','ev4'),
('er15','2024-05-03 10:00:00','confirmed',FALSE,'senior11','ev4'),
('er16','2024-05-20 10:00:00','confirmed',TRUE,'senior1','ev5'),
('er17','2024-05-21 11:00:00','confirmed',TRUE,'senior9','ev5'),
('er18','2024-06-01 09:00:00','confirmed',TRUE,'senior3','ev6'),
('er19','2024-06-02 10:00:00','confirmed',TRUE,'senior5','ev6'),
('er20','2024-06-02 11:00:00','cancelled',FALSE,'senior12','ev6'),
('er21','2024-07-05 14:00:00','confirmed',TRUE,'senior1','ev7'),
('er22','2024-07-06 10:00:00','confirmed',TRUE,'senior7','ev7'),
('er23','2024-07-06 11:00:00','confirmed',TRUE,'senior9','ev7'),
('er24','2024-07-07 09:00:00','confirmed',TRUE,'senior11','ev7'),
('er25','2024-09-10 09:00:00','confirmed',FALSE,'senior2','ev9'),
('er26','2024-09-11 10:00:00','confirmed',FALSE,'senior4','ev9'),
('er27','2024-09-12 11:00:00','confirmed',FALSE,'senior10','ev9'),
('er28','2024-09-12 14:00:00','confirmed',FALSE,'senior12','ev9'),
('er29','2024-09-25 10:00:00','confirmed',TRUE,'senior1','ev10'),
('er30','2024-09-26 11:00:00','confirmed',TRUE,'senior6','ev10');

INSERT INTO products VALUES
('prod1','Déambulateur léger','Matériel médical',89.00,15,42,'En stock'),
('prod2','Pilulier hebdomadaire','Santé',12.50,80,130,'En stock'),
('prod3','Téléassistance bracelet','Sécurité',149.00,8,18,'En stock'),
('prod4','Siège de douche','Matériel médical',55.00,20,35,'En stock'),
('prod5','Livre de sudoku senior','Loisirs',8.90,50,95,'En stock'),
('prod6','Loupe de lecture','Accessoires',19.90,30,60,'En stock'),
('prod7','Appareil de massage','Bien-être',45.00,5,22,'Stock faible');

INSERT INTO orders VALUES
('ord1','CMD-001','senior1',101.50,'2024-03-05 10:00:00','Livraison à domicile','Livré'),
('ord2','CMD-002','senior3',55.00,'2024-04-12 11:00:00','Retrait en agence','Livré'),
('ord3','CMD-003','senior7',149.00,'2024-05-20 14:00:00','Livraison à domicile','En cours'),
('ord4','CMD-004','senior2',21.40,'2024-06-01 09:00:00','Livraison à domicile','Livré'),
('ord5','CMD-005','senior10',64.90,'2024-07-15 10:00:00','Livraison à domicile','Livré'),
('ord6','CMD-006','senior5',89.00,'2024-09-01 11:00:00','Retrait en agence','Livré'),
('ord7','CMD-007','senior8',12.50,'2024-10-10 09:00:00','Livraison à domicile','En attente'),
('ord8','CMD-008','senior11',45.00,'2024-11-05 14:00:00','Livraison à domicile','Livré');

INSERT INTO order_items VALUES
('ord1','prod1',1,89.00),('ord1','prod2',1,12.50),
('ord2','prod4',1,55.00),
('ord3','prod3',1,149.00),
('ord4','prod2',1,12.50),('ord4','prod5',1,8.90),
('ord5','prod6',1,19.90),('ord5','prod5',2,8.90),('ord5','prod2',2,12.50),
('ord6','prod1',1,89.00),
('ord7','prod2',1,12.50),
('ord8','prod7',1,45.00);

INSERT INTO provider_missions VALUES
('mis1','Aide ménagère hebdomadaire','Nettoyage et repassage chez Mme Petit','2024-02-10','Acceptee','prest1','2024-02-05 09:00:00','2024-01-30 10:00:00'),
('mis2','Soins infirmiers','Pansements et suivi médical','2024-02-15','Acceptee','prest2','2024-02-10 10:00:00','2024-02-01 09:00:00'),
('mis3','Transport médical','Accompagnement consultation','2024-03-01','Acceptee','prest1','2024-02-25 11:00:00','2024-02-15 08:00:00'),
('mis4','Jardinage','Tonte et entretien','2024-03-20','Acceptee','prest4','2024-03-15 14:00:00','2024-03-05 10:00:00'),
('mis5','Aide ménagère','Nettoyage complet','2024-04-05','Acceptee','prest1','2024-03-30 09:00:00','2024-03-20 11:00:00'),
('mis6','Mission proposée','Nouvelle mission à pourvoir',NULL,'Proposee',NULL,NULL,'2024-04-15 10:00:00'),
('mis7','Soins à domicile','Suivi post-opératoire','2024-05-10','Acceptee','prest2','2024-05-05 10:00:00','2024-04-28 09:00:00');

INSERT INTO provider_invoices VALUES
('pinv1','prest1','2024-02',270.00,'Payée','2024-03-01 10:00:00'),
('pinv2','prest2','2024-02',126.00,'Payée','2024-03-01 11:00:00'),
('pinv3','prest1','2024-03',378.00,'Payée','2024-04-01 10:00:00'),
('pinv4','prest4','2024-03',162.00,'Payée','2024-04-01 11:00:00'),
('pinv5','prest1','2024-04',216.00,'Payée','2024-05-01 10:00:00'),
('pinv6','prest2','2024-05',252.00,'Payée','2024-06-01 11:00:00'),
('pinv7','prest1','2024-05',108.00,'Brouillon','2024-06-01 10:00:00');

INSERT INTO provider_payments VALUES
('ppay1','pinv1','prest1',270.00,'2024-03-10 10:00:00','Payé'),
('ppay2','pinv2','prest2',126.00,'2024-03-10 11:00:00','Payé'),
('ppay3','pinv3','prest1',378.00,'2024-04-10 10:00:00','Payé'),
('ppay4','pinv4','prest4',162.00,'2024-04-10 11:00:00','Payé'),
('ppay5','pinv5','prest1',216.00,'2024-05-10 10:00:00','Payé'),
('ppay6','pinv6','prest2',252.00,'2024-06-10 11:00:00','Payé'),
('ppay7','pinv7','prest1',NULL,NULL,'En attente');

INSERT INTO contents VALUES
('cnt1','Bienvenue sur Silver Happy','Actualités','<p>Découvrez nos services...</p>','Publié','2023-06-01 10:00:00',245,'admin1'),
('cnt2','Comment demander une prestation ?','Tutoriel','<p>Étape 1 : connectez-vous...</p>','Publié','2023-07-15 11:00:00',180,'admin1'),
('cnt3','Prévenir les chutes à domicile','Santé','<p>Conseils pratiques...</p>','Publié','2023-09-01 09:00:00',312,'admin1'),
('cnt4','Nos prestataires certifiés','Services','<p>Liste des prestataires...</p>','Brouillon','2024-01-10 10:00:00',0,'admin1');

INSERT INTO notifications VALUES
('notif1','info','Bienvenue !','Bienvenue sur Silver Happy, Jean.','2023-03-01 09:05:00',TRUE,'senior1'),
('notif2','devis','Nouveau devis','Votre devis DEV-001 est disponible.','2024-02-02 10:05:00',TRUE,'senior1'),
('notif3','info','Bienvenue !','Bienvenue sur Silver Happy, Marie.','2023-03-15 09:05:00',TRUE,'senior2'),
('notif4','devis','Nouveau devis','Votre devis DEV-002 est disponible.','2024-02-06 11:05:00',TRUE,'senior2'),
('notif5','facture','Facture disponible','Votre facture FACT-001 est prête.','2024-02-10 12:00:00',TRUE,'senior1'),
('notif6','rappel','Événement demain','L\'atelier mémoire commence demain.','2024-03-14 18:00:00',TRUE,'senior1'),
('notif7','devis','Nouveau devis','Votre devis DEV-009 est disponible.','2024-04-23 10:05:00',FALSE,'senior10'),
('notif8','facture','Facture disponible','Votre facture FACT-019 est prête.','2024-12-01 12:00:00',FALSE,'senior1');
