/*
  xandri03, xbilto00
  Klub anonymnich alkoholiku
  zmeny oproti ER-diagramu:
  * entitni mnozina 'Patron' nema polozku 'pocet_sverencu' (lze odvodit)
  * entitni mnozina 'Alkoholik' nema polozku 'pocet_sezeni_tento_rok'
    (lze odvodit)
  * entitni mnozina 'Alkoholik' nema polozku 'datum_posledniho_sezeni'
    (lze odvodit)
  * polozky 'datum' a 'cas' u entitnich mnozin 'Schuzka' a 'Sezeni' jsou
    slouceny do jedne polozky 'datum'
  * primarni kil entintni mnoziny 'Alkohol' je slozeny: je dan jak typem, tak
    i puvodem
  * kardinalita vztahu mezi mnozinami 'Kontrola' a 'Alkohol' je pozmenena na
    n:1..n (jedna kontrola muze zaznamenat vice druhu alkoholu; jeden druh
    alkoholu muze byt zjisten pri vice kontrolach
*/

-- smazeme tabulky vytvorene drive
DROP TABLE Osoba CASCADE CONSTRAINTS;
DROP TABLE Alkoholik CASCADE CONSTRAINTS;
DROP TABLE Patron CASCADE CONSTRAINTS;
DROP TABLE Odbornik CASCADE CONSTRAINTS;
DROP TABLE Kontrola CASCADE CONSTRAINTS;
DROP TABLE Alkohol CASCADE CONSTRAINTS;
DROP TABLE Sezeni CASCADE CONSTRAINTS;
DROP TABLE Misto CASCADE CONSTRAINTS;
DROP TABLE Schuzka CASCADE CONSTRAINTS;
DROP TABLE Patron_podporuje CASCADE CONSTRAINTS;
DROP TABLE Odbornik_dohlizi CASCADE CONSTRAINTS;
DROP TABLE Patron_sezeni CASCADE CONSTRAINTS;
DROP TABLE Odbornik_sezeni CASCADE CONSTRAINTS;
DROP TABLE Alkoholik_sezeni CASCADE CONSTRAINTS;
DROP TABLE Alkohol_zjisten CASCADE CONSTRAINTS;
-- smazeme sekvence
DROP SEQUENCE osoba_seq;
-- smazeme indexy
DROP INDEX kontrola_mira;
-- smazeme pohledy
DROP MATERIALIZED VIEW alkoholici_muzi;

-- vytvorime hlavni tabulky
CREATE TABLE Osoba
  (
    id_osoba INT PRIMARY KEY,
    jmeno          CHAR(30) NOT NULL,
    datum_narozeni DATE,
    pohlavi        CHAR(1), -- M jako muz, Z jako zena
    CHECK((pohlavi = 'M') or (pohlavi = 'Z'))
  );

CREATE TABLE Alkoholik
  ( id_osoba INT PRIMARY KEY REFERENCES Osoba(id_osoba)
  );

CREATE TABLE Patron
  ( id_osoba INT PRIMARY KEY REFERENCES Osoba(id_osoba)
  );

CREATE TABLE Odbornik
  (
    id_osoba INT PRIMARY KEY REFERENCES Osoba(id_osoba),
    expertiza CHAR(100),
    praxe     CHAR(100)
  );

CREATE TABLE Kontrola
  (
    id_kontrola INT GENERATED AS IDENTITY PRIMARY KEY,
    datum       DATE,
    mira        DECIMAL (4,2), -- v promile
    check (mira > 0.0)
  );

CREATE TABLE Alkohol
  (
    typ CHAR(20),
    puvod CHAR(20),
    CONSTRAINT PK_alkohol PRIMARY KEY (typ, puvod)
  );

CREATE TABLE Sezeni
  (
    id_sezeni INT GENERATED AS IDENTITY PRIMARY KEY,
    datum DATE
  );

CREATE TABLE Misto
  (
    id_misto INT PRIMARY KEY,
    adresa CHAR(50) NOT NULL
  );

CREATE TABLE Schuzka
  (
    id_schuzka INT GENERATED AS IDENTITY PRIMARY KEY,
    datum DATE
  );

-- pridame vztahy mezi entitnimi mnozinami
-- zacneme vztahy 1:n
-- osoba je vedoucim sezeni
ALTER TABLE Sezeni ADD vedouci INT REFERENCES Osoba(id_osoba) NOT NULL;
-- alkoholik se ucastni schuzky
ALTER TABLE Schuzka ADD alkoholik INT REFERENCES Alkoholik(id_osoba) NOT NULL;
-- patron se ucastni schuzky
ALTER TABLE Schuzka ADD patron INT REFERENCES Patron(id_osoba) NOT NULL;
-- sezeni se kona v miste
ALTER TABLE Sezeni ADD misto INT REFERENCES Misto(id_misto);
-- kontrola se tyka alkoholika
ALTER TABLE Kontrola ADD alkoholik INT REFERENCES Alkoholik(id_osoba) NOT NULL;
-- kontrola byla provedena odbornikem (ale nemusi)
ALTER TABLE Kontrola ADD odbornik INT REFERENCES Odbornik(id_osoba);

-- pro vztahy n:n potrebujeme vazebni tabulky
-- patron podporuje alkoholika
CREATE TABLE Patron_podporuje
  (
    patron INT REFERENCES Patron(id_osoba) NOT NULL,
    alkoholik INT REFERENCES Alkoholik(id_osoba) NOT NULL
  );
-- odbornik dohlizi na alkoholika
CREATE TABLE Odbornik_dohlizi
  (
    odbornik INT REFERENCES Odbornik(id_osoba) NOT NULL,
    alkoholik INT REFERENCES Alkoholik(id_osoba) NOT NULL
  );
-- patron se ucastni sezeni
CREATE TABLE Patron_sezeni
  (
    patron INT REFERENCES Patron(id_osoba) NOT NULL,
    sezeni INT REFERENCES Sezeni(id_sezeni) NOT NULL
  );
-- odbornik se ucastni sezeni
CREATE TABLE Odbornik_sezeni
  (
    odbornik INT REFERENCES Odbornik(id_osoba) NOT NULL,
    sezeni INT REFERENCES Sezeni(id_sezeni) NOT NULL
  );
-- alkoholik se ucastni sezeni
CREATE TABLE Alkoholik_sezeni
  (
    alkoholik INT REFERENCES Alkoholik(id_osoba) NOT NULL,
    sezeni INT REFERENCES Sezeni(id_sezeni) NOT NULL
  );
-- alkohol byl zjisten pri kontrole
CREATE TABLE Alkohol_zjisten
  (
    typ CHAR(20),
    puvod CHAR(20),
    kontrola INT REFERENCES Kontrola(id_kontrola),
    CONSTRAINT FK_alkohol FOREIGN KEY (typ, puvod) references Alkohol(typ, puvod)
  );

-- vytvorime triggery
-- unikatni sekvence id pro osobu
CREATE SEQUENCE osoba_seq START WITH 1 INCREMENT BY 1;

/*
  trigger zajistujici generovani unikatniho id pro tabulu Osoba kdyz vkladame
  radek s id_osoba rovnym NULL
*/
CREATE OR REPLACE TRIGGER Osoba_unique
BEFORE INSERT ON Osoba
FOR EACH ROW
BEGIN
  IF :NEW.id_osoba is NULL THEN
    :NEW.id_osoba := osoba_seq.NEXTVAL;
  END IF;
END;
/

/*
  trigger nastavujici novou hodnotu (resp. null) u odkazu na misto v tabulce
  Sezeni pokud prislusne misto bylo zmeneno (resp. smazano)
  (viz. ukazka po naplneni tabulek)
*/
CREATE OR REPLACE TRIGGER sezeni_misto_null
  AFTER DELETE OR UPDATE OF id_misto on Misto
  FOR EACH ROW
  BEGIN
    IF DELETING THEN
      UPDATE Sezeni SET Sezeni.misto = NULL
      WHERE Sezeni.misto = :OLD.id_misto;
    END IF;
    IF UPDATING AND :OLD.id_misto != :NEW.id_misto THEN
      UPDATE Sezeni SET Sezeni.misto = :NEW.id_misto
      WHERE Sezeni.misto = :OLD.id_misto;
    END IF;
  END;
/

/*
  trigger zajistujici maximalni pocet alkoholiku zucastnenych se sezeni
  (viz. ukazka po naplneni tabulek
*/
CREATE OR REPLACE TRIGGER sezeni_max_alkoholiku
  BEFORE INSERT ON Alkoholik_sezeni
  FOR EACH ROW
  DECLARE
    max_alkoholiku INT := 3;  -- podle zadani 12, 3 pro ucely demonstrace
    ted_alkoholiku INT; -- aktualni pocet alkoholiku
  BEGIN
    -- nacteme aktualni pocet alkoholiku v danem sezeni
    SELECT COUNT(*) into ted_alkoholiku
    FROM Alkoholik_sezeni
    WHERE sezeni = :NEW.sezeni;
    -- kdyz je plno, vyvolej vyjimku
    IF ted_alkoholiku = max_alkoholiku THEN
      raise_application_error(-20000, 'Maximalne 3 alkoholiky!');
    END IF;
  END;
/

-- naplnime tabulku daty
-- vytvorime osob
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Lynton Winfred', TO_DATE('04.10.1961', 'dd.mm.yyyy'), 'M');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Jennifer Marley', TO_DATE('14.05.1962', 'dd.mm.yyyy'), 'Z');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Brenden Winton', TO_DATE('03.10.1991', 'dd.mm.yyyy'), 'M');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Ward Henry', TO_DATE('25.05.1994', 'dd.mm.yyyy'), 'M');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Adela Judi', TO_DATE('11.04.1961', 'dd.mm.yyyy'), 'Z');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Glenna Gladwyn', TO_DATE('21.01.1988', 'dd.mm.yyyy'), 'Z');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Serenity Denzil', TO_DATE('06.05.1989', 'dd.mm.yyyy'), 'Z');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Stan Bazza', TO_DATE('16.07.1997', 'dd.mm.yyyy'), 'M');
INSERT INTO Osoba(jmeno, datum_narozeni, pohlavi) VALUES('Tawnie Lacy', TO_DATE('17.04.2008', 'dd.mm.yyyy'), 'Z');

-- 1-5 jsou alkoholici
INSERT INTO Alkoholik(id_osoba) VALUES(1);
INSERT INTO Alkoholik(id_osoba) VALUES(2);
INSERT INTO Alkoholik(id_osoba) VALUES(3);
INSERT INTO Alkoholik(id_osoba) VALUES(4);
INSERT INTO Alkoholik(id_osoba) VALUES(5);
-- 6-7 jsou patroni
INSERT INTO Patron(id_osoba) VALUES(6);
INSERT INTO Patron(id_osoba) VALUES(7);
-- 8-9 jsou odbornici
INSERT INTO Odbornik(id_osoba, expertiza, praxe) VALUES(8, 'ڞasna Stanova expertiza', 'Seznam Stanovy praxe');
INSERT INTO Odbornik(id_osoba, expertiza, praxe) VALUES(9, 'ڞasna Tawniova expertiza', 'Seznam Tawniovy praxe');

-- 6 podporuje 1-3, 7 podporuje 4-5
INSERT INTO Patron_podporuje(patron, alkoholik) VALUES(6, 1);
INSERT INTO Patron_podporuje(patron, alkoholik) VALUES(6, 2);
INSERT INTO Patron_podporuje(patron, alkoholik) VALUES(6, 3);
INSERT INTO Patron_podporuje(patron, alkoholik) VALUES(7, 4);
INSERT INTO Patron_podporuje(patron, alkoholik) VALUES(7, 5);
-- 8 dohlizi na 1-4, 9 dohlizi na 5
INSERT INTO Odbornik_dohlizi(odbornik, alkoholik) VALUES(8, 1);
INSERT INTO Odbornik_dohlizi(odbornik, alkoholik) VALUES(8, 2);
INSERT INTO Odbornik_dohlizi(odbornik, alkoholik) VALUES(8, 3);
INSERT INTO Odbornik_dohlizi(odbornik, alkoholik) VALUES(8, 4);
INSERT INTO Odbornik_dohlizi(odbornik, alkoholik) VALUES(9, 5);

-- vytvorime schuzky mezi alkoholiky a patrony
INSERT INTO Schuzka(datum, alkoholik, patron) VALUES(to_date('16.04.2006', 'dd.mm.yyyy'), 1, 6);
INSERT INTO Schuzka(datum, alkoholik, patron) VALUES(to_date('23.07.2012', 'dd.mm.yyyy'), 1, 6);
INSERT INTO Schuzka(datum, alkoholik, patron) VALUES(to_date('01.12.2012', 'dd.mm.yyyy'), 3, 6);
INSERT INTO Schuzka(datum, alkoholik, patron) VALUES(to_date('15.10.2014', 'dd.mm.yyyy'), 4, 7);
INSERT INTO Schuzka(datum, alkoholik, patron) VALUES(to_date('17.09.2016', 'dd.mm.yyyy'), 5, 7);

-- vytvorime seznam oficialnich mist
INSERT INTO Misto(id_misto, adresa) VALUES(1, '37 Lake View Court');
INSERT INTO Misto(id_misto, adresa) VALUES(2, '497 E. Iroquois St.');
INSERT INTO Misto(id_misto, adresa) VALUES(3, '451 Greenrose St.');

-- vytvorime ruzna sezeni
INSERT INTO Sezeni(datum, vedouci, misto) VALUES(to_date('16.03.2007', 'dd.mm.yyyy'), 6, 2);
INSERT INTO Sezeni(datum, vedouci, misto) VALUES(to_date('03.07.2017', 'dd.mm.yyyy'), 9, 3);

-- odbornici 8 a 9 se zucastnili 1. sezeni, nikdo z odborniku se ne zucastnil 2.
INSERT INTO Odbornik_sezeni(odbornik, sezeni) VALUES(8, 1);
INSERT INTO Odbornik_sezeni(odbornik, sezeni) VALUES(9, 1);
-- patron 6 se zucasntil 1. sezeni, patron 7 se zucastnil 2.
INSERT INTO Patron_sezeni(patron, sezeni) VALUES(6, 1);
INSERT INTO Patron_sezeni(patron, sezeni) VALUES(7, 2);
-- alkoholici 2 a 5 se zucastnili 1. sezeni,  1 2 a 4 se zucastnili 2.
INSERT INTO Alkoholik_sezeni(alkoholik, sezeni) VALUES(2, 1);
INSERT INTO Alkoholik_sezeni(alkoholik, sezeni) VALUES(5, 1);
INSERT INTO Alkoholik_sezeni(alkoholik, sezeni) VALUES(1, 2);
INSERT INTO Alkoholik_sezeni(alkoholik, sezeni) VALUES(2, 2);
INSERT INTO Alkoholik_sezeni(alkoholik, sezeni) VALUES(4, 2);

-- vytvorime typy alkoholu
INSERT INTO Alkohol(typ, puvod) VALUES('Beer', 'Czech Republic');
INSERT INTO Alkohol(typ, puvod) VALUES('Beer', 'Germany');
INSERT INTO Alkohol(typ, puvod) VALUES('Wine', 'France');
INSERT INTO Alkohol(typ, puvod) VALUES('Lager', 'Germany');
INSERT INTO Alkohol(typ, puvod) VALUES('Sake', 'Japan');

-- vytvorime kontroly
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('15.10.2017', 'dd.mm.yyyy'), 1.5, 2, 9);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('29.01.2017', 'dd.mm.yyyy'), 0.7, 1, NULL);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('08.08.2017', 'dd.mm.yyyy'), 0.3, 5, 8);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('23.08.2017', 'dd.mm.yyyy'), 0.6, 4, NULL);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('24.08.2017', 'dd.mm.yyyy'), 0.1, 3, 9);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('26.09.2017', 'dd.mm.yyyy'), 1.9, 2, 9);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('10.10.2017', 'dd.mm.yyyy'), 1.3, 1, 9);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('09.12.2017', 'dd.mm.yyyy'), 1.0, 5, 8);
INSERT INTO Kontrola(datum, mira, alkoholik, odbornik) VALUES(to_date('31.12.2017', 'dd.mm.yyyy'), 0.5, 3, NULL);

-- provazeme alkohol a kontroly
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Beer', 'Czech Republic', 1);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Beer', 'Czech Republic', 2);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Beer', 'Germany', 3);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Sake', 'Japan', 4);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Wine', 'France', 5);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Beer', 'Germany', 6);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Beer', 'Czech Republic', 7);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Beer', 'Czech Republic', 8);
INSERT INTO Alkohol_zjisten(typ, puvod, kontrola) VALUES('Lager', 'Germany', 9);

-- tabulky naplneny

/*
  procedura simulujici zasilani upozorneni alkoholikum kteri se v danem roce
  (2017) nezucastnili zadneho sezeni.
*/
CREATE OR REPLACE PROCEDURE alkoholick_check (id_alkoholik IN Alkoholik.id_osoba%TYPE) AS
navstivil BOOLEAN := FALSE;
rok INT;
alkoholik_buf INT;
sezeni_buf INT;
CURSOR kurzor IS
		SELECT alkoholik, sezeni
    FROM Alkoholik_sezeni
    WHERE alkoholik = id_alkoholik;
BEGIN
OPEN kurzor;
		LOOP
			FETCH kurzor INTO alkoholik_buf, sezeni_buf;
			EXIT WHEN kurzor%NOTFOUND;
      -- najdi rok sezeni
      SELECT EXTRACT(YEAR FROM datum) into rok
      FROM Sezeni
      WHERE id_sezeni = sezeni_buf;
      -- kdyz 2017, neposilej
      IF (rok = 2017) THEN
        navstivil := TRUE;
      END IF;
		END LOOP;
		CLOSE kurzor;
    IF (NOT navstivil) THEN
      dbms_output.put('Posilam upozorneni: ');
      dbms_output.put_line(id_alkoholik);
    ELSE
      dbms_output.put(id_alkoholik);
      dbms_output.put_line(' je v poradku');
    END IF;
    
END;
/

-- procedura menici jmeno
CREATE OR REPLACE PROCEDURE osoba_zmen_jmeno
(id_cil IN Osoba.id_osoba%TYPE, nove_jmeno IN Osoba.jmeno%TYPE)
  AS
	id_buf INTEGER;
	jmeno_buf CHAR(30);
  Jmeno_existuje EXCEPTION;
	CURSOR kurzor IS
		SELECT id_osoba, jmeno FROM Osoba WHERE id_osoba = id_cil;
BEGIN
		OPEN kurzor;
		LOOP
			FETCH kurzor INTO id_buf, jmeno_buf;
			EXIT WHEN kurzor%NOTFOUND;
			IF jmeno_buf = nove_jmeno THEN
        RAISE Jmeno_existuje;
      ELSE
         UPDATE Osoba SET Osoba.jmeno = nove_jmeno WHERE Osoba.id_osoba=id_cil;
      END IF;
		END LOOP;
		CLOSE kurzor;
    EXCEPTION
      WHEN Jmeno_existuje THEN
        dbms_output.put_line('Neni co menit!');
END;
/

-- PREZENTACE

-- ukazka triggeru osoba_unique
--SELECT * FROM Osoba;

-- ukazka triggeru sezeni_misto_null
/*
SELECT * FROM Misto;
SELECT * FROM Sezeni;
UPDATE Misto SET id_misto = 42 WHERE id_misto = 2;
SELECT * FROM Misto;
SELECT * FROM Sezeni;
DELETE FROM Misto WHERE id_misto = 3;
SELECT * FROM Misto;
SELECT * FROM Sezeni;
*/

-- ukazka triggeru sezeni_max_alkoholiku
/*
SELECT * FROM Alkoholik_sezeni;
INSERT INTO Alkoholik_sezeni(alkoholik, sezeni) VALUES(5, 2);
SELECT * FROM Alkoholik_sezeni;
*/

-- ukazka procedury alkoholick_check: mel by zaslat zpravu alkoholikum 3 a 5
/*
SELECT * FROM Sezeni;
SELECT * FROM Alkoholik_sezeni;
EXECUTE alkoholick_check(1);
EXECUTE alkoholick_check(2);
EXECUTE alkoholick_check(3);
EXECUTE alkoholick_check(4);
EXECUTE alkoholick_check(5);
*/

-- ukazka procedury osoba_zmen_jmeno
/*
SELECT * FROM Osoba;
EXECUTE osoba_zmen_jmeno(2, 'Honza Novak');
-- uspech, 2 zmeni jmeno
SELECT * FROM Osoba;
EXECUTE osoba_zmen_jmeno(2, 'Honza Novak');
-- neuspech
SELECT * FROM Osoba;
*/

-- ukazka pouzitelnosti indexu
/*
-- co je v tabulkach kontrola, alkohol zjisten
SELECT * FROM Kontrola;
SELECT * FROM Alkohol_zjisten;
-- analyzujeme nasledujici dotaz: kolik celkove bylo zjisteno alkoholu
-- (agregovano podle typu a puvodu) pri kontrolach s mirou vetsi nebo rovnou 1.0
EXPLAIN PLAN FOR
  SELECT A.typ, A.puvod, SUM(mira) FROM Kontrola K, Alkohol_zjisten A
  WHERE K.id_kontrola = A.kontrola AND K.mira >= 1.0
  GROUP BY A.typ, A.puvod;
SELECT plan_table_output FROM table(dbms_xplan.display());
-- vytvorime index nad mirou alkoholu pri kontrole
CREATE INDEX kontrola_mira ON Kontrola (mira);
-- analyzujeme jeste jednou
*/

-- vytvorime pohled na alkoholiky muzi
CREATE MATERIALIZED VIEW alkoholici_muzi
  REFRESH ON COMMIT AS
    SELECT *
    FROM Osoba O NATURAL JOIN Alkoholik A
    WHERE pohlavi = 'M';

-- definujeme pristupova prava pro xbilto00
GRANT ALL PRIVILEGES ON alkoholici_muzi to xbilto00;

-- toto spousti xbilto00
/*
ALTER SESSION SET CURRENT_SCHEMA = xandri03;
SELECT * FROM alkoholici_muzi;
*/