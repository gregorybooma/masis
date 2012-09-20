CREATE DATABASE masis
  WITH ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_US.UTF-8'
       LC_CTYPE = 'en_US.UTF-8'
       CONNECTION LIMIT = -1;

/* Create data types */

CREATE TYPE annstat AS ENUM (
    'incomplete',
    'complete'
);

CREATE TYPE substrdom AS ENUM (
    'dominant',
    'subdominant'
);

/* Create tables */

CREATE TABLE image_info
(
    id SERIAL,
    altitude double precision NOT NULL,
    depth double precision NOT NULL,
    area double precision,
    img_dir varchar NOT NULL,
    file_name varchar NOT NULL,
    annotation_status annstat,

    PRIMARY KEY (id),
    UNIQUE (img_dir, file_name)
);

CREATE TABLE substrate_types
(
    name varchar NOT NULL,
    description varchar,

    PRIMARY KEY (name)
);

INSERT INTO substrate_types (name) VALUES ('boulder');
INSERT INTO substrate_types (name) VALUES ('cobble');
INSERT INTO substrate_types (name) VALUES ('Didemnum');
INSERT INTO substrate_types (name) VALUES ('epifauna (unspecified)');
INSERT INTO substrate_types (name) VALUES ('gravel');
INSERT INTO substrate_types (name) VALUES ('manmade object');
INSERT INTO substrate_types (name) VALUES ('mud');
INSERT INTO substrate_types (name) VALUES ('mussels');
INSERT INTO substrate_types (name) VALUES ('pebble');
INSERT INTO substrate_types (name) VALUES ('sand');
INSERT INTO substrate_types (name) VALUES ('shells');
INSERT INTO substrate_types (name) VALUES ('silt');

CREATE TABLE image_substrate
(
    image_info_id integer NOT NULL,
    substrate_type varchar NOT NULL,
    dominance substrdom NOT NULL,

    UNIQUE (image_info_id, substrate_type),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (substrate_type) REFERENCES substrate_types (name)
);

CREATE TABLE image_tag_types
(
    name varchar NOT NULL,
    description varchar,

    PRIMARY KEY (name)
);

INSERT INTO image_tag_types (name) VALUES ('altitude too high');
INSERT INTO image_tag_types (name) VALUES ('cannot see seafloor');
INSERT INTO image_tag_types (name) VALUES ('difficult image');
INSERT INTO image_tag_types (name) VALUES ('flag for review');
INSERT INTO image_tag_types (name) VALUES ('highlight');
INSERT INTO image_tag_types (name) VALUES ('image corrupt');
INSERT INTO image_tag_types (name) VALUES ('out of focus');
INSERT INTO image_tag_types (name) VALUES ('reviewed');
INSERT INTO image_tag_types (name) VALUES ('turbid');
INSERT INTO image_tag_types (name) VALUES ('unusable');

CREATE TABLE image_tags
(
    image_info_id integer NOT NULL,
    image_tag varchar NOT NULL,

    UNIQUE (image_info_id, image_tag),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (image_tag) REFERENCES image_tag_types (name)
);

CREATE TABLE species
(
    aphia_id integer NOT NULL,
    lsid varchar,
    scientific_name varchar NOT NULL,
    status varchar,
    valid_aphia_id integer,
    valid_name varchar,
    kingdom varchar,
    phylum varchar,
    class varchar,
    "order" varchar,
    family varchar,
    genus varchar,

    PRIMARY KEY (aphia_id)
);

CREATE TABLE vectors
(
    id SERIAL,
    image_info_id integer NOT NULL,
    aphia_id integer,
    vector_id varchar NOT NULL,
    vector_wkt varchar NOT NULL,
    area_pixels integer,
    area_m2 double precision,
    created_by varchar NOT NULL, -- creator user ID
    updated_by varchar, -- updater user ID
    updated_on timestamp DEFAULT now() NOT NULL, -- creation/update time
    remarks varchar(50),

    PRIMARY KEY (id),
    UNIQUE (image_info_id,vector_id),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (aphia_id) REFERENCES species (aphia_id)
);

CREATE TABLE areas_image_grouped
(
    image_info_id integer NOT NULL,
    aphia_id integer NOT NULL,
    species_area double precision NOT NULL,
    image_area double precision NOT NULL

    PRIMARY KEY (image_info_id, aphia_id),
    FOREIGN KEY (image_info_id) REFERENCES image_info (id),
    FOREIGN KEY (aphia_id) REFERENCES species (aphia_id)
);

CREATE TABLE IF NOT EXISTS users (
  user_id varchar NOT NULL, -- this is an email address (user@mit.edu)
  pass_hash varchar NOT NULL, -- preferably bcrypt hash
  first_name varchar,

  PRIMARY KEY (user_id)
);

CREATE TABLE IF NOT EXISTS users_logged (
  user_id varchar NOT NULL,
  hash varchar NOT NULL, -- remember_me_hash cookie hash for the login session

  FOREIGN KEY (user_id) REFERENCES users (user_id)
);

/* Creatie indexes */

CREATE INDEX ON image_info (img_dir);
