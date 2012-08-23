CREATE DATABASE masis
  WITH ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_US.UTF-8'
       LC_CTYPE = 'en_US.UTF-8'
       CONNECTION LIMIT = -1;

/* Create tables */

CREATE TYPE annstat AS ENUM ('incomplete','complete','moderate','review');

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

CREATE TABLE vectors
(
    id SERIAL,
    image_info_id INTEGER NOT NULL,
    species_id INTEGER,
    vector_id VARCHAR NOT NULL,
    vector_wkt VARCHAR NOT NULL,
    area_pixels INTEGER,
    area_m2 DOUBLE PRECISION,
    remarks VARCHAR(50),

    PRIMARY KEY (id),
    UNIQUE (image_info_id,vector_id),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (species_id) REFERENCES species (id)
);

CREATE TABLE areas_image_grouped
(
    id SERIAL,
    image_info_id INTEGER NOT NULL,
    species_id INTEGER NOT NULL,
    species_area DOUBLE PRECISION NOT NULL,
    image_area DOUBLE PRECISION NOT NULL,

    PRIMARY KEY (id),
    UNIQUE (image_info_id,species_id),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (species_id) REFERENCES species (id)
);
