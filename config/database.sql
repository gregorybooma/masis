CREATE DATABASE masis
  WITH ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_US.UTF-8'
       LC_CTYPE = 'en_US.UTF-8'
       CONNECTION LIMIT = -1;

/* Create functions */

CREATE FUNCTION round (DOUBLE PRECISION, INTEGER) RETURNS DOUBLE PRECISION AS
'select cast(round(cast($1 as numeric),$2) as double precision);'
LANGUAGE SQL with(iscachable);

/* Create data types */

CREATE TYPE annstat AS ENUM ('incomplete','complete','moderate','review');

/* Create tables */

CREATE TABLE image_info
(
    id SERIAL,
    altitude DOUBLE PRECISION NOT NULL,
    "depth" DOUBLE PRECISION NOT NULL,
    area DOUBLE PRECISION,
    img_dir VARCHAR NOT NULL,
    file_name VARCHAR NOT NULL,
    annotation_status annstat,

    PRIMARY KEY (id),
    UNIQUE (img_dir, file_name)
);
CREATE INDEX ON image_info (img_dir);

CREATE TABLE species
(
    aphia_id INTEGER NOT NULL,
    lsid VARCHAR,
    scientific_name VARCHAR NOT NULL,
    status VARCHAR,
    valid_aphia_id INTEGER,
    valid_name VARCHAR,
    kingdom VARCHAR,
    phylum VARCHAR,
    class VARCHAR,
    "order" VARCHAR,
    family VARCHAR,
    genus VARCHAR,

    PRIMARY KEY (aphia_id)
);

CREATE TABLE vectors
(
    id SERIAL,
    image_info_id INTEGER NOT NULL,
    aphia_id INTEGER,
    vector_id VARCHAR NOT NULL,
    vector_wkt VARCHAR NOT NULL,
    area_pixels INTEGER,
    area_m2 DOUBLE PRECISION,
    remarks VARCHAR(50),

    PRIMARY KEY (id),
    UNIQUE (image_info_id,vector_id),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (aphia_id) REFERENCES species (aphia_id)
);

CREATE TABLE areas_image_grouped
(
    id SERIAL,
    image_info_id INTEGER NOT NULL,
    aphia_id INTEGER NOT NULL,
    species_area DOUBLE PRECISION NOT NULL,
    image_area DOUBLE PRECISION NOT NULL,

    PRIMARY KEY (id),
    UNIQUE (image_info_id,aphia_id),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (aphia_id) REFERENCES species (aphia_id)
);

CREATE TABLE IF NOT EXISTS users (
  id SERIAL,
  username VARCHAR NOT NULL,
  password VARCHAR NOT NULL,
  email VARCHAR NOT NULL,
  "date" DATE NOT NULL,

  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS users_logged (
  id INTEGER NOT NULL,
  hash VARCHAR NOT NULL,

  FOREIGN KEY (id) REFERENCES users (id)
);
