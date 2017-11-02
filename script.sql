-- drop all tables
DROP TABLE IF EXISTS person;
DROP TABLE IF EXISTS alcoholic;
DROP TABLE IF EXISTS patron;
DROP TABLE IF EXISTS expert;
DROP TABLE IF EXISTS patron_supports;
DROP TABLE IF EXISTS expert_supervises;
DROP TABLE IF EXISTS meeting;
DROP TABLE IF EXISTS place;
DROP TABLE IF EXISTS session;
DROP TABLE IF EXISTS person_attends;
DROP TABLE IF EXISTS alcohol;
DROP TABLE IF EXISTS report;

-- Person
CREATE TABLE person (
	email		VARCHAR(64) PRIMARY KEY,
	password	VARCHAR(64) NOT NULL,
	name		VARCHAR(64) NOT NULL,
	birthdate	DATE,
	gender		CHAR(1), -- 'M' is for 'male', 'F' is for 'female'
	picture		LONGBLOB,
	CHECK((gender = 'M') or (gender = 'Z'))
);

-- Alcoholic, patron and expert
CREATE TABLE alcoholic (
	email	VARCHAR(64) PRIMARY KEY REFERENCES person(email)
);
CREATE TABLE patron (
	email	VARCHAR(64) PRIMARY KEY REFERENCES person(email)
);
CREATE TABLE expert (
	email		VARCHAR(64) PRIMARY KEY REFERENCES person(email),
	education	VARCHAR(256),
    practice	VARCHAR(256)
);

-- Patron supports, expert supervises
CREATE TABLE patron_supports (
	patron		VARCHAR(64) REFERENCES patron(email),
	alcoholic	VARCHAR(64) REFERENCES alcoholic(email),
	PRIMARY KEY (patron, alcoholic)
);
CREATE TABLE expert_supervises (
	expert		VARCHAR(64) REFERENCES expert(email),
	alcoholic	VARCHAR(64) REFERENCES alcoholic(email),
	PRIMARY KEY (expert, alcoholic)
);

-- Meeting
CREATE TABLE meeting (
	id			INT PRIMARY KEY AUTO_INCREMENT,
	patron		VARCHAR(64) REFERENCES patron(email),
	alcoholic	VARCHAR(64) REFERENCES alcoholic(email),
	date 		DATE NOT NULL
);

-- Place, session
CREATE TABLE place (
	id		INT PRIMARY KEY  AUTO_INCREMENT,
    address	VARCHAR(64) NOT NULL
);
CREATE TABLE session (
	id		INT PRIMARY KEY  AUTO_INCREMENT,
	date	DATE NOT NULL,
	place	INT REFERENCES place(id),
	leader	VARCHAR(64) REFERENCES person(email)
);
CREATE TABLE person_attends (
	email		VARCHAR(64) REFERENCES person(email),
	session		INT REFERENCES session(id),
	PRIMARY KEY (email, session)
);

-- Alcohol, alcohol report
CREATE TABLE alcohol (
	id		INT PRIMARY KEY  AUTO_INCREMENT,
	type	VARCHAR(32),
	origin	VARCHAR(32)
);
CREATE TABLE report (
	id			INT PRIMARY KEY  AUTO_INCREMENT,
	date		DATE NOT NULL,
	bac			FLOAT NOT NULL,
	alcohol		INT REFERENCES alcohol(id),
	alcoholic	VARCHAR(64) REFERENCES alcoholic(email),
	expert		VARCHAR(64) REFERENCES expert(email),
	CHECK ((bac > 0.0) and (bac <= 1.0))
);

-- Populate
INSERT INTO person (email, password, name, birthdate, gender, picture) VALUES
	('allen@a.a', 'a', 'Woody Allen', NULL, NULL, NULL),
	('blanchett@a.a', 'a', 'Cate Blanchett', NULL, NUll, NULL),
	('caine@a.a', 'a', 'Michael Caine', NULL, NUll, NULL),
	('chaplin@a.a', 'a', 'Charles Chaplin', NULL, NUll, NULL),
	('cuaron@a.a', 'a', 'Alfonso Cuaron', NULL, NUll, NULL),
	('deakins@a.a', 'a', 'Roger Deakins', NULL, NUll, NULL),
	('freeman@a.a', 'a', 'Morgan Freeman', NULL, NUll, NULL),
	('hanks@a.a', 'a', 'Tom Hanks', NULL, NUll, NULL),
	('hepburn@a.a', 'a', 'Audrey Hepburn' , NULL, NUll, NULL),
	('lubezki@a.a', 'a', 'Emmanuel Lubezki' , NULL, NUll, NULL),
	('niro@a.a', 'a', 'Robert de Nito' , NULL, NUll, NULL),
	('nolan@a.a', 'a', 'Christopher Nolan' , NULL, NUll, NULL),
	('oldman@a.a', 'a', 'Gary Oldman' , NULL, NUll, NULL),
	('otoole@a.a', 'a', 'Peter OToole' , NULL, NUll, NULL),
	('pacino@a.a', 'a', 'Al Pacino' , NULL, NUll, NULL),
	('pfister@a.a', 'a', 'Wally Pfister' , NULL, NUll, NULL),
	('reed@a.a', 'a', 'Donna Reed' , NULL, NULL, NULL),
	('sandgren@a.a', 'a', 'Linus Sandgren' , NULL, NULL, NULL),
	('scorcese@a.a', 'a', 'Martin Scorcese' , NULL, NULL, NULL),
	('stewart@a.a', 'a', 'James Stewart' , NULL, NULL, NULL),
	('villeneuve@a.a', 'a', 'Denis Villeneuve' , NULL, NULL, NULL),
	('welles@a.a', 'a', 'Orson Welles' , NULL, NULL, NULL),
	('young@a.a', 'a', 'Freddie Young' , NULL, NULL, NULL)
;
INSERT INTO alcoholic (email) VALUES
	('blanchett@a.a'), ('caine@a.a'), ('freeman@a.a'), ('hanks@a.a'),
	('hepburn@a.a'), ('niro@a.a'), ('oldman@a.a'), ('otoole@a.a'), 
	('pacino@a.a'), ('reed@a.a'), ('stewart@a.a')
;
INSERT INTO patron (email) VALUES
	('allen@a.a'), ('chaplin@a.a'), ('cuaron@a.a'), ('nolan@a.a'), 
	('scorcese@a.a'), ('villeneuve@a.a'), ('welles@a.a')
;
INSERT INTO expert (email, education, practice) VALUES
	('deakins@a.a', NULL, NULL), ('lubezki@a.a', NULL, NULL), 
	('pfister@a.a', NULL, NULL), ('sandgren@a.a', NULL, NULL),
	('young@a.a', NULL, NULL)
;

INSERT INTO patron_supports (patron, alcoholic) VALUES
	('allen@a.a', 'blanchett@a.a'), ('allen@a.a', 'caine@a.a'),
	('chaplin@a.a', 'freeman@a.a'), ('chaplin@a.a', 'hanks@a.a'),
	('cuaron@a.a', 'hepburn@a.a'), ('cuaron@a.a', 'niro@a.a'),
	('nolan@a.a', 'oldman@a.a'), ('nolan@a.a', 'otoole@a.a'),
	('scorcese@a.a', 'pacino@a.a'), ('scorcese@a.a', 'reed@a.a'),
	('villeneuve@a.a', 'stewart@a.a'), ('villeneuve@a.a', 'blanchett@a.a'),
	('welles@a.a', 'freeman@a.a'), ('welles@a.a', 'hepburn@a.a')
;
INSERT INTO expert_supervises (expert, alcoholic) VALUES
	('deakins@a.a', 'oldman@a.a'), ('deakins@a.a', 'pacino@a.a'),
	('lubezki@a.a', 'stewart@a.a'), ('lubezki@a.a', 'reed@a.a'),
	('pfister@a.a', 'otoole@a.a'), ('pfister@a.a', 'niro@a.a'),
	('sandgren@a.a', 'hanks@a.a'), ('sandgren@a.a', 'caine@a.a'),
	('young@a.a', 'stewart@a.a'), ('young@a.a', 'hepburn@a.a')
;

INSERT INTO alcohol (type, origin) VALUES
	('wine', 'France'), ('beer', 'Germany')
;
