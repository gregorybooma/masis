CREATE DATABASE masis
  WITH ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_US.UTF-8'
       LC_CTYPE = 'en_US.UTF-8'
       CONNECTION LIMIT = -1;

/* Create tables */

CREATE TABLE image_info
(
    id SERIAL,
    altitude DOUBLE PRECISION NOT NULL,
    "depth" DOUBLE PRECISION NOT NULL,
    area DOUBLE PRECISION,
    img_dir VARCHAR NOT NULL,
    file_name VARCHAR NOT NULL,

    PRIMARY KEY (id),
    UNIQUE (img_dir, file_name)
);
CREATE INDEX ON image_info (img_dir);

CREATE TABLE species
(
    id SERIAL,
    name_latin VARCHAR(50) NOT NULL,
    name_venacular VARCHAR(50),
    native BOOLEAN,
    invasive BOOLEAN,
    description VARCHAR(100),
    remarks VARCHAR(100),

    PRIMARY KEY (id),
    UNIQUE (name_latin),
    UNIQUE (name_venacular)
);

/* Insert data */

COPY image_info (altitude,depth,area,img_dir,file_name)
    FROM 'image_info.csv'
    DELIMITER ',' CSV HEADER;

INSERT INTO species (name_latin, native, invasive) VALUES ('Didemnum vexillum', 'f', 't');
INSERT INTO species (name_latin, name_venacular) VALUES ('Haliclona oculata', 'finger sponge');
INSERT INTO species (name_latin, name_venacular) VALUES ('Isodictya palmata', 'palmate sponge');
INSERT INTO species (name_latin, name_venacular) VALUES ('Microciona prolifera', 'red-beard sponge');
INSERT INTO species (name_latin, name_venacular) VALUES ('Suberites ficus', 'fig sponge');
INSERT INTO species (name_latin, name_venacular) VALUES ('Terebratulina septentrionalis', 'brachiopod');
