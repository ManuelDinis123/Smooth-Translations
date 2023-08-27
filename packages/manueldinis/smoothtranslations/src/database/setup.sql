CREATE TABLE st_langs (
	id int unsigned auto_increment not null,
	language varchar(64) not null,
    primary key (id, language)
);

CREATE TABLE st_texts (
	id int unsigned auto_increment not null,
	text text not null,
    primary key (id)
);

CREATE TABLE st_translations (
	id int unsigned auto_increment not null,
    lang_id int unsigned not null,
	text_id int unsigned not null,
    translation text not null,
    FOREIGN KEY (text_id) REFERENCES st_texts(id),
    FOREIGN KEY (lang_id) REFERENCES st_langs(id),
    primary key (id)
);