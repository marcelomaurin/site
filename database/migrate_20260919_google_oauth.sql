USE maurinsoft;

ALTER TABLE usuarios
  ADD COLUMN google_sub VARCHAR(255) NULL AFTER email,
  ADD COLUMN avatar_url VARCHAR(500) NULL AFTER google_sub,
  ADD UNIQUE KEY uq_usuarios_google_sub (google_sub);
