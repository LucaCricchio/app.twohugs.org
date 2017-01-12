-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `countries`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `countries` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `iso_alpha_3` CHAR(3) NOT NULL,
  PRIMARY KEY (`id`, `iso_alpha_3`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NULL COMMENT 'Non è usato nella registrazione, ma eventualmente per mostrarlo agli altri utenti. (un nickname)',
  `first_name` VARCHAR(50) NULL,
  `last_name` VARCHAR(50) NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NULL,
  `birth_date` DATE NULL,
  `gender` CHAR(1) NULL,
  `telephone` VARCHAR(50) NULL,
  `avatar` VARCHAR(255) NULL COMMENT 'Contiene il percorso relativo al profilo dell\'utente.',
  `facebook_user_id` VARCHAR(100) NULL,
  `google_user_id` VARCHAR(100) NULL,
  `activation_code` VARCHAR(100) NULL COMMENT 'Contiene il codice di attivazione quando l\'utente si registra  con la propria email.',
  `activation_date` DATETIME NULL COMMENT 'Contiene la data in cui l\'utente ha attivato il proprio account',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `last_login` DATETIME NULL,
  `country` INT NULL,
  `city` VARCHAR(50) NULL,
  `address` VARCHAR(100) NULL,
  `zipcode` VARCHAR(10) NULL,
  `status` TINYINT UNSIGNED NULL COMMENT 'Contiene lo stato dell\'utente:\n[0] => Non disponibile\n[1] => Disponibile\n[2] => Bloccato in attesa di risposta per un abbraccio (in pratica quando viene fatto il fetch)\n[3] => Abbraccio in corso\n[4] => In ricerca (sta cercando un abbraccio)\n',
  `geo_latitude` DECIMAL(10,7) NULL,
  `geo_longitude` DECIMAL(10,7) NULL,
  `geo_last_update` DATETIME NULL COMMENT 'Contiene la data dell\'ultimo aggiornamento riguardo la posizione.',
  `blocked` TINYINT(1) NULL DEFAULT 0 COMMENT 'Contiene un boolean che descrivi se l\'utente è stato bloccato o meno. (E\' utile per bloccare un account utente)',
  `completed` TINYINT(1) NULL DEFAULT 0 COMMENT 'Indica se il profilo dell\'utente è completo (i profili non completi non possono né ricerca né essere trovati)',
  `gcm_device_id` VARCHAR(255) NULL,
  `max_duration` INT NOT NULL DEFAULT 0,
  `max_distance` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `country_idx` (`country` ASC),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC),
  UNIQUE INDEX `activation_code_UNIQUE` (`activation_code` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `searches`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `searches` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `keep_alive` DATETIME NOT NULL COMMENT 'Contiene la data e ora dell\'ultimo segnale di keep_search_alive.\nIMPORTANTE: Il keep search alive verrà per default inviato ogni tot secondi. Tuttavia è possibile che il server modifiche il timer di A in base a determinate situazioni. \nAd esempio: suppo' /* comment truncated */ /*niamo che il keep_search_alive sia inviato ogni 45 secondi e il tempo massimo disponbile da un utente B per rispondere sia di 35 secondi.
Supponiamo che A avvi la ricerca al tempo 0, S elabora la lista e prende allo stesso tempo il primo B della lista. Mettiamo caso che B rifiuti al tempo 20 e S estrae il secondo B al tempo 22. Il nuovo 'utente B viene contattato ed avrà 35 secondi per rispondere, ovvero avrà come deadline il tempo 57. Al tempo 45 arriva il keep_search_alive di A. 
In questo caso S non dovrà continuare la ricerca (estraendo un nuovo B) ma dovrà comunicare ad A di re-inviare il keep_search_alive dopo 57 - 45 + scarto di 10 secondi. Ovvero S comunicherà ad A di re-inviare il keep_search_alive dopo 22 secondi. */,
  `stopped` TINYINT(1) NULL COMMENT 'Se l\'utente ha bloccato intenzionalmente la ricerca, questo campo contiene true (Questo campo non viene coinvolto qualora l\'utente termina la ricerca per \"durata massima\")',
  `max_duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Durata massima, in secondi, della ricerca. E\' dfinito dalle impostazione dell\'utente.',
  `max_distance` INT NOT NULL DEFAULT 0,
  `finished_at` DATETIME NULL COMMENT 'Indica la data in cui è finita la ricerca. Se è stata stoppata dall\'utente, conterrà lo stesso valore di stopped_at',
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `timeout` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica se la ricerca è stata terminata per timeout di tempo massimo.',
  `geo_latitude` DECIMAL(10,7) NOT NULL,
  `geo_longitude` DECIMAL(10,7) NOT NULL,
  `ip` VARCHAR(15) NOT NULL,
  `extra` TINYTEXT NULL COMMENT 'Contiene una stringa json contenente dati extra. Utili per essere recuperati velocemente o altri utilizza. Ad esempio l\'id dell\'utente B che accetta l\'abbraccio. In questo modo lo ripeschiamo velocemente.',
  PRIMARY KEY (`id`),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `search_lists`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `search_lists` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `search_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `search_id_idx` (`search_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `search_list_users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `search_list_users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `search_list_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `order` INT NULL COMMENT 'Rappresenta l\'ordine degli utenti nella lista. Naturalmente è ordinata sullo stesso valore di search_list_id. L\'ordine sarà crescente per distanza.',
  `fetched_at` DATETIME NULL COMMENT 'Data di estrazione dell\'elemento. Rappresenta la data e l\'ora di quando l\'utente è stato selezionato dalla lista. (E quindi la data di quando è stata inviata la richiesta)',
  `responsed_at` DATETIME NULL COMMENT 'Rappresenta la data e ora di quando l\'utente ha risposto alla richiesta. Resterà NULLA se non è avvenuta risposta.',
  `response_type` TINYINT UNSIGNED NULL COMMENT '1 = accettato, 2 = rifiutato, 3 = timeout connesso [ovvero l\'app ha comunicato col server che l\'utente non ha cliccato nè accetta, nè rifiuta].',
  PRIMARY KEY (`id`),
  INDEX `list_id_idx` (`search_list_id` ASC),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_search_timeouts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_search_timeouts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `search_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `timed_out_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `search_id_idx` (`search_id` ASC),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vips`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vips` (
  `id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hugs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `created_at` DATETIME NOT NULL,
  `search_id` INT NOT NULL,
  `user_seeker_id` INT NOT NULL COMMENT 'Contiene l\'ID dell\'utente che ha avviato la ricerca',
  `user_sought_id` INT NOT NULL COMMENT 'Contiene L\'id dell\'utente B che ha accettato l\'abbraccio [che è stato cercato]',
  `user_seeker_last_checkin` DATETIME NULL COMMENT 'Indica la data dell\'ultimo accesso/segnale inviato dall\'utente dall\'interno della stanza dell\'abbraccio. Questo campo è necessario per verificare che l\'utente sia attivo nella stanza dell\'abbraccio.',
  `user_sought_last_checkin` DATETIME NULL,
  `closed_at` DATETIME NULL COMMENT 'Indica quando è stato chiuso l\'abbraccio. Può essere chiuso da uno dei due utenti. Viene registrata la data  del primo utente che lo chiude',
  `closed_by` INT NULL COMMENT 'Contiene l\'id dell\'utente che ha chiuso l\'abbraccio.',
  `code` VARCHAR(50) NOT NULL COMMENT 'Contiene un codice univoco per l\'abbraccio. Utile per visualizzarlo agli utenti',
  `user_seeker_who_are_you_request` DATETIME NULL COMMENT 'Contiene la data della richiesta di who are you',
  `user_sought_who_are_you_request` DATETIME NULL,
  `timed_out_user_id` INT NULL COMMENT 'Id dell\'utente andando in timeout. Se un utente non manda più il segnale di checkIn per un tempo specifico, l\'altro utente, al momento del refresh, chiuderà in automatico l\'abbraccio.',
  PRIMARY KEY (`id`),
  INDEX `search_id_idx` (`search_id` ASC),
  INDEX `user_a_id_idx` (`user_seeker_id` ASC),
  INDEX `user_b_id_idx` (`user_sought_id` ASC),
  INDEX `closed_by_idx` (`closed_by` ASC),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_hug_feedbacks`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_hug_feedbacks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `hug_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `result` TINYINT NULL COMMENT '0 = Neutro, 1 = Positivo, -1 = Negativo',
  PRIMARY KEY (`id`),
  INDEX `user_id_idx` (`user_id` ASC),
  INDEX `hug_id_idx` (`hug_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_hug_selfies`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_hug_selfies` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `hug_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `file_path` VARCHAR(512) NOT NULL COMMENT 'Contiene il percorso relativo fino all\'immagine.',
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT NOT NULL COMMENT 'Numero di byte del file',
  PRIMARY KEY (`id`),
  INDEX `hug_id_idx` (`hug_id` ASC),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_friends`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_friends` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `friend_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_id_idx` (`user_id` ASC),
  INDEX `friend_id_idx` (`friend_id` ASC))
ENGINE = InnoDB
COMMENT = 'Contiene le referenze riguardo la funzionalità Who Are You.' /* comment truncated */ /* In pratica quando entrambi gli utenti "si accettano" (entro 24h dall'abbraccio) vengono inseriti due record in questa tabella che simboleggiano l'amicizia.*/;


-- -----------------------------------------------------
-- Table `vip_requests`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vip_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `fetched_at` DATETIME NULL COMMENT 'Contiene la data di quanto l\'utente è stato contattato e gli è stato proposto di diventare VIP',
  `responsed_at` DATETIME NULL COMMENT 'Indica la data in cui l\'utente ha risposto',
  `response_type` TINYINT NOT NULL DEFAULT 0 COMMENT '[0]  = Pendente\n[-1] = Rifiuto\n[1]  = Accettato',
  PRIMARY KEY (`id`),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vip_posts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vip_posts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `vip_id` INT NOT NULL,
  `title` VARCHAR(255) NULL COMMENT 'Eventuale titolo del post',
  `content` TINYTEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `vip_id_idx` (`vip_id` ASC))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
