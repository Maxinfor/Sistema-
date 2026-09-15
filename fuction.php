<?php
// includes/functions.php

function getConnection() {
    $host = 'localhost';
    $db   = 'conselho_db'; 
    $user = 'root';        
    $pass = '';            
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}

function listarProcessos($filtro = '') {
    $pdo = getConnection();
    if (!empty($filtro)) {
        $stmt = $pdo->prepare("SELECT * FROM processos WHERE pasta LIKE ? OR num_processo LIKE ? OR assunto LIKE ? OR cidade LIKE ? ORDER BY id DESC");
        $termo = "%$filtro%";
        $stmt->execute([$termo, $termo, $termo, $termo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $stmt = $pdo->query("SELECT * FROM processos ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function salvarProcesso($dados) {
    $pdo = getConnection();
    $criancasDados = is_array($dados['criancas_dados'] ?? null) ? json_encode($dados['criancas_dados']) : ($dados['criancas_dados'] ?? '[]');
    $responsaveisDados = is_array($dados['responsaveis_dados'] ?? null) ? json_encode($dados['responsaveis_dados']) : ($dados['responsaveis_dados'] ?? '[]');

    $stmt = $pdo->prepare("INSERT INTO processos (pasta, num_processo, tipo_pessoa, criancas_dados, responsaveis_dados, endereco, cidade, assunto, conselheiro, processo_sei, observacao, pasta_importada, data_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $dados['pasta'] ?? '',
        $dados['num_processo'] ?? '',
        $dados['tipo_pessoa'] ?? 'CRIANÇA',
        $criancasDados,
        $responsaveisDados,
        $dados['endereco'] ?? '',
        $dados['cidade'] ?? '',
        $dados['assunto'] ?? '',
        $dados['conselheiro'] ?? '',
        $dados['processo_sei'] ?? '',
        $dados['observacao'] ?? '',
        $dados['pasta_importada'] ?? 0,
        $dados['data_registro'] ?? date('Y-m-d')
    ]);
    return $pdo->lastInsertId();
}

function atualizarProcesso($id, $dados) {
    $pdo = getConnection();
    $criancasDados = is_array($dados['criancas_dados'] ?? null) ? json_encode($dados['criancas_dados']) : ($dados['criancas_dados'] ?? '[]');
    $responsaveisDados = is_array($dados['responsaveis_dados'] ?? null) ? json_encode($dados['responsaveis_dados']) : ($dados['responsaveis_dados'] ?? '[]');

    $stmt = $pdo->prepare("UPDATE processos SET pasta = ?, num_processo = ?, tipo_pessoa = ?, criancas_dados = ?, responsaveis_dados = ?, endereco = ?, cidade = ?, assunto = ?, conselheiro = ?, processo_sei = ?, observacao = ?, pasta_importada = ? WHERE id = ?");
    
    $stmt->execute([
        $dados['pasta'] ?? '',
        $dados['num_processo'] ?? '',
        $dados['tipo_pessoa'] ?? 'CRIANÇA',
        $criancasDados,
        $responsaveisDados,
        $dados['endereco'] ?? '',
        $dados['cidade'] ?? '',
        $dados['assunto'] ?? '',
        $dados['conselheiro'] ?? '',
        $dados['processo_sei'] ?? '',
        $dados['observacao'] ?? '',
        $dados['pasta_importada'] ?? 0,
        $id
    ]);
}

function excluirProcesso($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("DELETE FROM processos WHERE id = ?");
    $stmt->execute([$id]);
}

function buscarProcesso($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM processos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUltimoNumeroPasta($letra) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT pasta FROM processos WHERE pasta LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$letra . '-%']);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res && isset($res['pasta'])) {
        $partes = explode('-', $res['pasta']);
        return isset($partes[1]) ? (int)$partes[1] : 0;
    }
    return 0;
}

function listarAtendimentos() {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM atendimentos ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function salvarAtendimento($dados) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO atendimentos (nome, cidade, assunto, admin, observacao, data_hora, data_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $dados['nome'] ?? '',
        $dados['cidade'] ?? '',
        $dados['assunto'] ?? '',
        $dados['admin'] ?? '',
        $dados['observacao'] ?? '',
        $dados['data_hora'] ?? date('d/m/Y H:i'),
        $dados['data_registro'] ?? date('Y-m-d')
    ]);
    return $pdo->lastInsertId();
}

function atualizarAtendimento($id, $dados) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE atendimentos SET nome = ?, cidade = ?, assunto = ?, admin = ?, observacao = ? WHERE id = ?");
    $stmt->execute([
        $dados['nome'] ?? '',
        $dados['cidade'] ?? '',
        $dados['assunto'] ?? '',
        $dados['admin'] ?? '',
        $dados['observacao'] ?? '',
        $id
    ]);
}

function excluirAtendimento($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("DELETE FROM atendimentos WHERE id = ?");
    $stmt->execute([$id]);
}

function buscarAtendimento($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM atendimentos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function listarHistorico($processoId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM historico_servicos WHERE processo_id = ? ORDER BY id DESC");
    $stmt->execute([$processoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function salvarHistorico($processoId, $dados) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO historico_servicos (processo_id, data_servico, conselheiro, descricao) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $processoId,
        $dados['data_servico'] ?? date('Y-m-d'),
        $dados['conselheiro'] ?? '',
        $dados['descricao'] ?? ''
    ]);
}

function excluirHistorico($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("DELETE FROM historico_servicos WHERE id = ?");
    $stmt->execute([$id]);
}

function getDashboardEstatisticas($mes = null, $ano = null) {
    $pdo = getConnection();
    $where = "";
    $params = [];
    if ($mes && $mes !== 'todos' && $ano) {
        $where = " WHERE MONTH(data_registro) = ? AND YEAR(data_registro) = ? ";
        $params = [$mes, $ano];
    } elseif ($ano) {
        $where = " WHERE YEAR(data_registro) = ? ";
        $params = [$ano];
    }
    
    $stmt = $pdo->prepare("SELECT assunto, COUNT(*) as total FROM processos $where GROUP BY assunto ORDER BY total DESC");
    $stmt->execute($params);
    $resultado = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['assunto'])) {
            $resultado[$row['assunto']] = $row['total'];
        }
    }
    return $resultado;
}

function getDashboardCidades($mes = null, $ano = null) {
    $pdo = getConnection();
    $where = "";
    $params = [];
    if ($mes && $mes !== 'todos' && $ano) {
        $where = " WHERE MONTH(data_registro) = ? AND YEAR(data_registro) = ? ";
        $params = [$mes, $ano];
    } elseif ($ano) {
        $where = " WHERE YEAR(data_registro) = ? ";
        $params = [$ano];
    }
    
    $stmt = $pdo->prepare("SELECT cidade, COUNT(*) as total FROM processos $where GROUP BY cidade ORDER BY total DESC");
    $stmt->execute($params);
    $resultado = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['cidade'])) {
            $resultado[$row['cidade']] = $row['total'];
        }
    }
    return $resultado;
}

function importarProcessosEmLote($dadosLote) {
    $sucessos = 0;
    $erros = 0;
    $mensagens = [];

    foreach ($dadosLote as $item) {
        try {
            $criancasArr = !empty($item['criancas']) ? [['nome' => $item['criancas'], 'nasc' => $item['nasc'] ?? '']] : [];
            $respArr = !empty($item['responsavel']) ? [$item['responsavel']] : [];

            $dadosProcesso = [
                'pasta' => $item['pasta'] ?? 'A-1',
                'num_processo' => $item['num_processo'] ?? '',
                'tipo_pessoa' => 'CRIANÇA',
                'criancas_dados' => json_encode($criancasArr),
                'responsaveis_dados' => json_encode($respArr),
                'endereco' => $item['endereco'] ?? '',
                'cidade' => $item['cidade'] ?? 'Paranoá',
                'assunto' => $item['assunto'] ?? '',
                'conselheiro' => $item['conselheiro'] ?? '',
                'processo_sei' => $item['processo_sei'] ?? '',
                'observacao' => '',
                'pasta_importada' => 1,
                'data_registro' => date('Y-m-d')
            ];
            salvarProcesso($dadosProcesso);
            $sucessos++;
        } catch (Exception $e) {
            $erros++;
            $mensagens[] = $e->getMessage();
        }
    }

    return ['sucessos' => $sucessos, 'erros' => $erros, 'mensagens' => $mensagens];
}
