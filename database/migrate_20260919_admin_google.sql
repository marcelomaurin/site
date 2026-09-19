USE maurinsoft;

UPDATE usuarios
SET papel='admin', plano_id=NULL, ativo=1
WHERE LOWER(email)='marcelomaurinmartins@gmail.com';
