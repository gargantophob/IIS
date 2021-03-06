-- reset database
DROP DATABASE if exists xandri03;
CREATE DATABASE xandri03;
USE xandri03;

-- Person
CREATE TABLE person (
	email		VARCHAR(64) PRIMARY KEY,
	password	VARCHAR(64) NOT NULL,
	name		VARCHAR(64) NOT NULL,
	birthdate	DATE,
	gender		CHAR(1), -- 'M' is for 'male', 'F' is for 'female'
	picture		LONGBLOB,
	CHECK((gender = 'M') or (gender = 'Z'))
) ENGINE=InnoDB;

-- Alcoholic, patron and expert
CREATE TABLE alcoholic (
  email     VARCHAR(64) NOT NULL PRIMARY KEY,
  CONSTRAINT FOREIGN KEY (email) REFERENCES person(email) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE patron (
  email     VARCHAR(64) NOT NULL PRIMARY KEY,
  CONSTRAINT FOREIGN KEY (email) REFERENCES person(email) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expert (
	email		VARCHAR(64) NOT NULL PRIMARY KEY,
	education	VARCHAR(256),
    practice	VARCHAR(256),
    CONSTRAINT FOREIGN KEY (email) REFERENCES person(email) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Patron supports, expert supervises
CREATE TABLE patron_supports (
	patron		VARCHAR(64),
	alcoholic	VARCHAR(64),
	PRIMARY KEY (patron, alcoholic),
    CONSTRAINT FOREIGN KEY (patron) REFERENCES patron(email) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (alcoholic) REFERENCES alcoholic(email) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expert_supervises (
	expert		VARCHAR(64),
	alcoholic	VARCHAR(64),
	PRIMARY KEY (expert, alcoholic),
    CONSTRAINT FOREIGN KEY (expert) REFERENCES expert(email) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (alcoholic) REFERENCES alcoholic(email) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Meeting
CREATE TABLE meeting (
	id			INT PRIMARY KEY AUTO_INCREMENT,
	patron		VARCHAR(64),
	alcoholic	VARCHAR(64),
	date 		DATE NOT NULL,
    CONSTRAINT FOREIGN KEY (patron) REFERENCES patron(email) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (alcoholic) REFERENCES alcoholic(email) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Place, session
CREATE TABLE place (
	id		INT PRIMARY KEY  AUTO_INCREMENT,
    address	VARCHAR(64) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE session (
	id		INT PRIMARY KEY  AUTO_INCREMENT,
	date	DATE NOT NULL,
	place	INT,
	leader	VARCHAR(64),
    CONSTRAINT FOREIGN KEY (place) REFERENCES place(id) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (leader) REFERENCES person(email) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE person_attends (
	email		VARCHAR(64),
	session		INT,
	PRIMARY KEY (email, session),
    CONSTRAINT FOREIGN KEY (email) REFERENCES person(email) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (session) REFERENCES session(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Alcohol, alcohol report
CREATE TABLE alcohol (
	id		INT PRIMARY KEY  AUTO_INCREMENT,
	type	VARCHAR(32),
	origin	VARCHAR(32)
) ENGINE=InnoDB;

CREATE TABLE report (
	id			INT PRIMARY KEY  AUTO_INCREMENT,
	date		DATE NOT NULL,
	bac			FLOAT NOT NULL,
	alcohol		INT,
	alcoholic	VARCHAR(64),
	expert		VARCHAR(64),
    CONSTRAINT FOREIGN KEY (alcohol) REFERENCES alcohol(id) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (alcoholic) REFERENCES alcoholic(email) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (expert) REFERENCES expert(email) ON DELETE CASCADE,
	CHECK ((bac > 0.0) and (bac <= 1.0))
) ENGINE=InnoDB;

-- Populate
INSERT INTO person (email, password, name, birthdate, gender, picture) VALUES
	('blanchett@mail.com', 'asdfasdf', 'Cate Blanchett', '1969-05-14', 'F', NULL),
	('caine@mail.com', 'asdfasdf', 'Michael Caine', '1933-03-14', 'M', NULL),
	('freeman@mail.com', 'asdfasdf', 'Morgan Freeman', '1937-06-01', 'M', NULL),
	('hanks@mail.com', 'asdfasdf', 'Tom Hanks', '1956-07-09', 'M', NULL),
	('hepburn@mail.com', 'asdfasdf', 'Audrey Hepburn' , '1929-05-04', 'F', NULL),
	('niro@mail.com', 'asdfasdf', 'Robert de Niro' , '1943-08-17', 'M', NULL),
	('oldman@mail.com', 'asdfasdf', 'Gary Oldman' , '1958-03-21', 'M', NULL),
	('otoole@mail.com', 'asdfasdf', 'Peter OToole' , '1932-08-02', 'M', NULL),
	('pacino@mail.com', 'asdfasdf', 'Al Pacino' , '1940-04-25', 'M', NULL),
	('reed@mail.com', 'asdfasdf', 'Donna Reed' , '1921-01-27', 'F', NULL),
	('stewart@mail.com', 'asdfasdf', 'James Stewart' , '1908-05-20', 'M', NULL),
	('allen@mail.com', 'asdfasdf', 'Woody Allen', NULL, 'M', NULL),
	('chaplin@mail.com', 'asdfasdf', 'Charles Chaplin', NULL, 'M', NULL),
	('cuaron@mail.com', 'asdfasdf', 'Alfonso Cuaron', NULL, 'M', NULL),
	('deakins@mail.com', 'asdfasdf', 'Roger Deakins', NULL, 'M', NULL),
	('lubezki@mail.com', 'asdfasdf', 'Emmanuel Lubezki' , NULL, 'M', NULL),
	('nolan@mail.com', 'asdfasdf', 'Christopher Nolan' , NULL, 'M', NULL),
	('pfister@mail.com', 'asdfasdf', 'Wally Pfister' , NULL, 'M', NULL),
	('sandgren@mail.com', 'asdfasdf', 'Linus Sandgren' , NULL, 'M', NULL),
	('scorcese@mail.com', 'asdfasdf', 'Martin Scorcese' , NULL, 'M', NULL),
	('villeneuve@mail.com', 'asdfasdf', 'Denis Villeneuve' , NULL, 'M', NULL),
	('welles@mail.com', 'asdfasdf', 'Orson Welles' , NULL, 'M', NULL),
	('young@mail.com', 'asdfasdf', 'Freddie Young' , NULL, 'M', NULL)
;
INSERT INTO alcoholic (email) VALUES
	('blanchett@mail.com'), ('caine@mail.com'), ('freeman@mail.com'),
	('hanks@mail.com'), ('hepburn@mail.com'), ('niro@mail.com'),
	('oldman@mail.com'), ('otoole@mail.com'), ('pacino@mail.com'),
	('reed@mail.com'), ('stewart@mail.com')
;
INSERT INTO patron (email) VALUES
	('allen@mail.com'), ('chaplin@mail.com'), ('cuaron@mail.com'),
	('nolan@mail.com'), ('scorcese@mail.com'), ('villeneuve@mail.com'),
	('welles@mail.com')
;
INSERT INTO expert (email, education, practice) VALUES
	('deakins@mail.com', NULL, NULL), ('lubezki@mail.com', NULL, NULL), 
	('pfister@mail.com', NULL, NULL), ('sandgren@mail.com', NULL, NULL),
	('young@mail.com', NULL, NULL)
;

INSERT INTO patron_supports (patron, alcoholic) VALUES
	('allen@mail.com', 'blanchett@mail.com'),
	('allen@mail.com', 'caine@mail.com'),
	('allen@mail.com', 'freeman@mail.com'),
	('allen@mail.com', 'hepburn@mail.com'),
	('chaplin@mail.com', 'freeman@mail.com'),
	('chaplin@mail.com', 'hanks@mail.com'),
	('chaplin@mail.com', 'hepburn@mail.com'),
	('chaplin@mail.com', 'caine@mail.com'),
	('cuaron@mail.com', 'hepburn@mail.com'),
	('cuaron@mail.com', 'niro@mail.com'),
	('cuaron@mail.com', 'oldman@mail.com'),
	('cuaron@mail.com', 'hanks@mail.com'),
	('nolan@mail.com', 'oldman@mail.com'),
	('nolan@mail.com', 'otoole@mail.com'),
	('nolan@mail.com', 'pacino@mail.com'),
	('nolan@mail.com', 'niro@mail.com'),
	('scorcese@mail.com', 'pacino@mail.com'),
	('scorcese@mail.com', 'reed@mail.com'),
	('scorcese@mail.com', 'stewart@mail.com'),
	('scorcese@mail.com', 'otoole@mail.com'),
	('villeneuve@mail.com', 'stewart@mail.com'),
	('villeneuve@mail.com', 'blanchett@mail.com'),
	('villeneuve@mail.com', 'freeman@mail.com'),
	('villeneuve@mail.com', 'reed@mail.com'),
	('welles@mail.com', 'freeman@mail.com'),
	('welles@mail.com', 'hepburn@mail.com'),
	('welles@mail.com', 'hanks@mail.com'),
	('welles@mail.com', 'blanchett@mail.com')
;
INSERT INTO expert_supervises (expert, alcoholic) VALUES
	('deakins@mail.com', 'blanchett@mail.com'),
	('deakins@mail.com', 'oldman@mail.com'),
	('deakins@mail.com', 'pacino@mail.com'),
	('lubezki@mail.com', 'stewart@mail.com'),
	('lubezki@mail.com', 'reed@mail.com'),
	('lubezki@mail.com', 'caine@mail.com'),
	('pfister@mail.com', 'otoole@mail.com'),
	('pfister@mail.com', 'niro@mail.com'),
	('pfister@mail.com', 'freeman@mail.com'),
	('sandgren@mail.com', 'hanks@mail.com'),
	('sandgren@mail.com', 'caine@mail.com'),
	('sandgren@mail.com', 'niro@mail.com'),
	('young@mail.com', 'stewart@mail.com'),
	('young@mail.com', 'reed@mail.com'),
	('young@mail.com', 'hepburn@mail.com')
;

INSERT INTO alcohol (type, origin) VALUES
	('wine', 'France'), ('beer', 'Germany'), ('beer', 'Czechia'),
	('rum', 'Cuba'), ('chacha', 'Georgia'), ('vodka', 'Russia'),
	('gin', 'England')
;

INSERT INTO report (date, bac, alcohol, alcoholic, expert) VALUES
	('2017-11-24', 0.01, 1, 'blanchett@mail.com', NULL),
	('2017-11-23', 0.02, 2, 'oldman@mail.com', 'deakins@mail.com'),
	('2017-11-22', 0.04, 3, 'stewart@mail.com', 'lubezki@mail.com'),
	('2017-11-21', 0.08, 4, 'stewart@mail.com', NULL),
	('2017-11-20', 0.16, 5, 'niro@mail.com', 'pfister@mail.com'),
	('2017-11-19', 0.08, 6, 'reed@mail.com', NULL),
	('2017-11-18', 0.16, 7, 'hepburn@mail.com', 'young@mail.com'),
	('2017-11-17', 0.16, 6, 'niro@mail.com', NULL)
;

INSERT INTO meeting (patron, alcoholic, date) VALUES
	('allen@mail.com', 'blanchett@mail.com', '2018-02-01'),
	('allen@mail.com', 'caine@mail.com', '2018-02-02'),
	('allen@mail.com', 'freeman@mail.com', '2018-02-03'),
	('allen@mail.com', 'hepburn@mail.com', '2018-02-04'),
	('chaplin@mail.com', 'freeman@mail.com', '2018-02-05'),
	('chaplin@mail.com', 'hanks@mail.com', '2018-02-06'),
	('chaplin@mail.com', 'hepburn@mail.com', '2018-02-07'),
	('chaplin@mail.com', 'caine@mail.com', '2018-02-08'),
	('cuaron@mail.com', 'hepburn@mail.com', '2018-02-09'),
	('cuaron@mail.com', 'niro@mail.com', '2018-02-10'),
	('cuaron@mail.com', 'oldman@mail.com', '2018-02-11'),
	('cuaron@mail.com', 'hanks@mail.com', '2018-02-12'),
	('nolan@mail.com', 'oldman@mail.com', '2018-02-13'),
	('nolan@mail.com', 'otoole@mail.com', '2018-02-14'),
	('nolan@mail.com', 'pacino@mail.com', '2018-02-15'),
	('nolan@mail.com', 'niro@mail.com', '2018-02-16'),
	('scorcese@mail.com', 'pacino@mail.com', '2018-02-17'),
	('scorcese@mail.com', 'reed@mail.com', '2018-02-18'),
	('scorcese@mail.com', 'stewart@mail.com', '2018-02-19'),
	('scorcese@mail.com', 'otoole@mail.com', '2018-02-20'),
	('villeneuve@mail.com', 'stewart@mail.com', '2018-02-21'),
	('villeneuve@mail.com', 'blanchett@mail.com', '2018-02-22'),
	('villeneuve@mail.com', 'freeman@mail.com', '2018-02-23'),
	('villeneuve@mail.com', 'reed@mail.com', '2018-02-24'),
	('welles@mail.com', 'freeman@mail.com', '2018-02-25'),
	('welles@mail.com', 'hepburn@mail.com', '2018-02-26'),
	('welles@mail.com', 'hanks@mail.com', '2018-02-27'),
	('welles@mail.com', 'blanchett@mail.com', '2018-02-28')
;

INSERT INTO place (address) VALUES
	('221B Baker Street, London'),
	('42 Wallaby Way, Sydney'),
	('1600 Pennsylvania Avenue NW, Washington'),
	('12 Grimmauld Place, London')
;

INSERT INTO session (date, place, leader) VALUES
	('2018-01-01', '1', 'welles@mail.com'),
	('2018-01-06', '2', 'chaplin@mail.com'),
	('2018-01-11', '3', 'lubezki@mail.com'),
	('2018-01-16', '4', 'caine@mail.com'),
	('2018-01-21', '1', 'reed@mail.com')
;

INSERT INTO person_attends (email, session) VALUES
	('welles@mail.com', '1'),
	('chaplin@mail.com', '2'),
	('lubezki@mail.com', '3'),
	('caine@mail.com', '4'),
	('reed@mail.com', '5'),
	('blanchett@mail.com', '3'),
	('blanchett@mail.com', '4'),
	('caine@mail.com', '1'),
	('caine@mail.com', '2'),
	('freeman@mail.com', '3'),
	('freeman@mail.com', '4'),
	('hanks@mail.com', '3'),
	('hanks@mail.com', '4'),
	('hepburn@mail.com', '5'),
	('hepburn@mail.com', '1'),
	('niro@mail.com', '1'),
	('niro@mail.com', '2'),
	('oldman@mail.com', '3'),
	('oldman@mail.com', '4'),
	('otoole@mail.com', '5'),
	('otoole@mail.com', '1'),
	('pacino@mail.com', '2'),
	('pacino@mail.com', '3'),
	('reed@mail.com', '1'),
	('reed@mail.com', '2'),
	('stewart@mail.com', '4'),
	('stewart@mail.com', '5'),
	('allen@mail.com', '1'),
	('allen@mail.com', '2'),
	('chaplin@mail.com', '1'),
	('chaplin@mail.com', '3'),
	('cuaron@mail.com', '3'),
	('cuaron@mail.com', '4'),
	('deakins@mail.com', '5'),
	('deakins@mail.com', '1'),
	('lubezki@mail.com', '1'),
	('lubezki@mail.com', '2'),
	('nolan@mail.com', '2'),
	('nolan@mail.com', '3'),
	('pfister@mail.com', '4'),
	('pfister@mail.com', '5'),
	('sandgren@mail.com', '1'),
	('sandgren@mail.com', '2'),
	('scorcese@mail.com', '3'),
	('scorcese@mail.com', '4'),
	('villeneuve@mail.com', '5'),
	('villeneuve@mail.com', '2'),
	('welles@mail.com', '2'),
	('welles@mail.com', '3'),
	('young@mail.com', '4'),
	('young@mail.com', '3')
;
