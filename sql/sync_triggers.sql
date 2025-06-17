DELIMITER //

CREATE TRIGGER after_empresa_insert 
AFTER INSERT ON sam.empresa
FOR EACH ROW
BEGIN
    -- Get the admin password from sam.adm
    SELECT senha INTO @admin_senha FROM sam.adm WHERE id_adm = NEW.adm_id;

    -- Insert into sam_emprego.empresas_recrutamento with admin password
    INSERT INTO sam_emprego.empresas_recrutamento (
        nome,
        email,
        senha,
        telefone,
        endereco,
        setor,
        tamanho,
        status
    )
    VALUES (
        NEW.nome,
        NEW.email_corp,
        @admin_senha,
        NEW.telefone,
        NEW.endereco,
        NEW.setor_atuacao,
        NEW.num_fun,
        'Ativo'
    );
END;
//

CREATE TRIGGER after_empresa_update
AFTER UPDATE ON sam.empresa
FOR EACH ROW
BEGIN
    -- Get the admin password from sam.adm
    SELECT senha INTO @admin_senha FROM sam.adm WHERE id_adm = NEW.adm_id;

    -- Update sam_emprego.empresas_recrutamento
    UPDATE sam_emprego.empresas_recrutamento
    SET 
        nome = NEW.nome,
        email = NEW.email_corp,
        senha = @admin_senha,
        telefone = NEW.telefone,
        endereco = NEW.endereco,
        setor = NEW.setor_atuacao,
        tamanho = NEW.num_fun
    WHERE email = OLD.email_corp;
END;
//

DELIMITER ;
