<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function usuarioAtual(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    $st = db()->prepare(
        'SELECT u.id,u.nome,u.email,u.papel,u.ativo,u.plano_id,
                p.codigo AS plano_codigo,p.nome AS plano_nome,p.tipo AS plano_tipo
           FROM usuarios u
      LEFT JOIN planos p ON p.id=u.plano_id
          WHERE u.id=? LIMIT 1'
    );
    $st->execute([(int)$_SESSION['usuario_id']]);
    $u = $st->fetch();

    if (!$u || !(int)$u['ativo']) {
        logoutLocal();
        return null;
    }
    return $u;
}

function exigirLogin(): array
{
    $u = usuarioAtual();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function exigirAdmin(): array
{
    $u = exigirLogin();
    if ($u['papel'] !== 'admin') {
        header('Location: ../index.php');
        exit;
    }
    return $u;
}

function loginUsuario(string $email, string $senha): bool
{
    $st = db()->prepare('SELECT * FROM usuarios WHERE email=? AND ativo=1 LIMIT 1');
    $st->execute([mb_strtolower(trim($email))]);
    $u = $st->fetch();

    if (!$u || !password_verify($senha, $u['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int)$u['id'];
    $_SESSION['papel'] = $u['papel'];

    db()->prepare('UPDATE usuarios SET ultimo_login=NOW() WHERE id=?')->execute([(int)$u['id']]);
    registrarAuditoria((int)$u['id'], 'LOGIN', 'autenticacao', null);
    return true;
}

function logoutLocal(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function registrarAuditoria(?int $usuarioId, string $acao, ?string $recurso, ?array $detalhe): void
{
    $st = db()->prepare(
        'INSERT INTO auditoria(usuario_id,acao,recurso,detalhe_json,ip)
         VALUES(?,?,?,?,?)'
    );
    $st->execute([
        $usuarioId,
        $acao,
        $recurso,
        $detalhe ? json_encode($detalhe, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function inicioPeriodo(string $periodo): string
{
    return match ($periodo) {
        'diario' => date('Y-m-d 00:00:00'),
        'semanal' => date('Y-m-d 00:00:00', strtotime('monday this week')),
        'anual' => date('Y-01-01 00:00:00'),
        'vitalicio' => '1970-01-01 00:00:00',
        default => date('Y-m-01 00:00:00'),
    };
}

function acessoServico(int $usuarioId, string $codigoServico): array
{
    $sql = "
    SELECT
        s.id AS servico_id,s.codigo,s.nome,s.descricao,s.unidade_consumo,
        COALESCE(us.permitido, ps.permitido, 0) AS permitido,
        CASE WHEN us.id IS NOT NULL THEN us.limite_quantidade ELSE ps.limite_quantidade END AS limite_quantidade,
        COALESCE(us.periodo, ps.periodo, 'mensal') AS periodo,
        us.inicio,us.fim
    FROM usuarios u
    JOIN servicos s ON s.codigo=? AND s.ativo=1
    LEFT JOIN usuario_servicos us
      ON us.usuario_id=u.id AND us.servico_id=s.id
    LEFT JOIN plano_servicos ps
      ON ps.plano_id=u.plano_id AND ps.servico_id=s.id
    WHERE u.id=? AND u.ativo=1
    LIMIT 1";
    $st = db()->prepare($sql);
    $st->execute([$codigoServico, $usuarioId]);
    $regra = $st->fetch();

    if (!$regra) {
        return ['permitido' => false, 'motivo' => 'SERVICO_INEXISTENTE'];
    }

    $agora = time();
    if (!empty($regra['inicio']) && strtotime($regra['inicio']) > $agora) {
        return ['permitido' => false, 'motivo' => 'FORA_DA_VIGENCIA', 'servico' => $regra];
    }
    if (!empty($regra['fim']) && strtotime($regra['fim']) < $agora) {
        return ['permitido' => false, 'motivo' => 'FORA_DA_VIGENCIA', 'servico' => $regra];
    }
    if (!(int)$regra['permitido']) {
        return ['permitido' => false, 'motivo' => 'NAO_CONCEDIDO', 'servico' => $regra];
    }

    $inicio = inicioPeriodo($regra['periodo']);
    $st = db()->prepare(
        'SELECT COALESCE(SUM(quantidade),0) total
           FROM consumo_servicos
          WHERE usuario_id=? AND servico_id=? AND criado_em>=?'
    );
    $st->execute([$usuarioId, (int)$regra['servico_id'], $inicio]);
    $usado = (float)$st->fetchColumn();
    $limite = $regra['limite_quantidade'] === null ? null : (float)$regra['limite_quantidade'];
    $restante = $limite === null ? null : max(0, $limite - $usado);

    return [
        'permitido' => $limite === null || $usado < $limite,
        'motivo' => ($limite !== null && $usado >= $limite) ? 'LIMITE_ATINGIDO' : 'OK',
        'servico' => $regra,
        'usado' => $usado,
        'limite' => $limite,
        'restante' => $restante,
        'periodo_inicio' => $inicio,
    ];
}

function consumirServico(int $usuarioId, string $codigoServico, float $quantidade = 1, ?string $referencia = null, ?array $meta = null): array
{
    if ($quantidade <= 0) {
        return ['ok' => false, 'motivo' => 'QUANTIDADE_INVALIDA'];
    }

    $acesso = acessoServico($usuarioId, $codigoServico);
    if (empty($acesso['permitido'])) {
        return ['ok' => false, 'motivo' => $acesso['motivo'], 'acesso' => $acesso];
    }

    if ($acesso['limite'] !== null && ($acesso['usado'] + $quantidade) > $acesso['limite']) {
        return ['ok' => false, 'motivo' => 'SALDO_INSUFICIENTE', 'acesso' => $acesso];
    }

    $st = db()->prepare(
        'INSERT INTO consumo_servicos(usuario_id,servico_id,quantidade,referencia,metadados_json)
         VALUES(?,?,?,?,?)'
    );
    $st->execute([
        $usuarioId,
        (int)$acesso['servico']['servico_id'],
        $quantidade,
        $referencia,
        $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
    ]);

    return ['ok' => true, 'acesso' => acessoServico($usuarioId, $codigoServico)];
}
