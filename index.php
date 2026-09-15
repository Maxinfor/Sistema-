<?php
// index.php
require_once 'includes/functions.php';

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

// Endpoints AJAX / Requisições
if ($acao) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($acao) {
        case 'listar_processos':
            $filtro = $_GET['filtro'] ?? '';
            echo json_encode(listarProcessos($filtro));
            exit;

        case 'salvar_processo':
            try {
                $id = salvarProcesso($_POST);
                echo json_encode(['success' => true, 'message' => 'Processo cadastrado com sucesso!', 'id' => $id]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'atualizar_processo':
            try {
                $id = $_POST['id'] ?? 0;
                atualizarProcesso($id, $_POST);
                echo json_encode(['success' => true, 'message' => 'Processo atualizado com sucesso!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'buscar_processo':
            $id = $_GET['id'] ?? 0;
            echo json_encode(buscarProcesso($id));
            exit;

        case 'excluir_processo':
            $id = $_POST['id'] ?? 0;
            excluirProcesso($id);
            echo json_encode(['success' => true]);
            exit;

        case 'gerar_pasta':
            $letra = strtoupper($_GET['letra'] ?? 'A');
            $num = getUltimoNumeroPasta($letra) + 1;
            echo json_encode(['pasta' => $letra . '-' . $num]);
            exit;

        case 'listar_atendimentos':
            echo json_encode(listarAtendimentos());
            exit;

        case 'salvar_atendimento':
            try {
                $id = salvarAtendimento($_POST);
                echo json_encode(['success' => true, 'message' => 'Atendimento salvo com sucesso!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'atualizar_atendimento':
            try {
                $id = $_POST['id'] ?? 0;
                atualizarAtendimento($id, $_POST);
                echo json_encode(['success' => true, 'message' => 'Atendimento atualizado com sucesso!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'buscar_atendimento':
            $id = $_GET['id'] ?? 0;
            echo json_encode(buscarAtendimento($id));
            exit;

        case 'excluir_atendimento':
            $id = $_POST['id'] ?? 0;
            excluirAtendimento($id);
            echo json_encode(['success' => true]);
            exit;

        case 'listar_historico':
            $processoId = $_GET['processo_id'] ?? 0;
            echo json_encode(listarHistorico($processoId));
            exit;

        case 'salvar_historico':
            $processoId = $_POST['processo_id'] ?? 0;
            salvarHistorico($processoId, $_POST);
            echo json_encode(['success' => true]);
            exit;

        case 'excluir_historico':
            $id = $_POST['id'] ?? 0;
            excluirHistorico($id);
            echo json_encode(['success' => true]);
            exit;

        case 'dashboard_assuntos':
            $mes = $_GET['mes'] ?? null;
            $ano = $_GET['ano'] ?? date('Y');
            echo json_encode(getDashboardEstatisticas($mes, $ano));
            exit;

        case 'dashboard_cidades':
            $mes = $_GET['mes'] ?? null;
            $ano = $_GET['ano'] ?? date('Y');
            echo json_encode(getDashboardCidades($mes, $ano));
            exit;

        case 'importar_processos_lote':
            $dadosJson = $_POST['dados'] ?? '[]';
            $dadosArr = json_decode($dadosJson, true);
            $resultado = importarProcessosEmLote($dadosArr);
            echo json_encode($resultado);
            exit;

        case 'apagar_tudo':
            try {
                $pdo = getConnection();
                $pdo->exec("DELETE FROM historico_servicos");
                $pdo->exec("DELETE FROM atendimentos");
                $pdo->exec("DELETE FROM processos");
                echo json_encode(['success' => true, 'message' => 'Todos os registros foram apagados com sucesso!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao apagar registros: ' . $e->getMessage()]);
            }
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGACTPAR - Sistema de Gestão do Conselho Tutelar</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SheetJS para importação de Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Lista oficial de assuntos permitidos pelo Conselho Tutelar e CDCA
        const listaAssuntosPermitidos = [
            "NEGLIGÊNCIA",
            "MAus TRATOS",
            "ABUSO SEXUAL",
            "EXPLORAÇÃO SEXUAL",
            "EVASÃO ESCOLAR",
            "TRABALHO INFANTIL",
            "ABRIGO / INSTITUCIONALIZAÇÃO",
            "MEDIDA DE PROTEÇÃO",
            "DISPUTA DE GUARDA",
            "DEPENDÊNCIA QUÍMICA (PAIS/RESPONSÁVEIS)",
            "VULNERABILIDADE SOCIAL",
            "SAÚDE MENTAL / PSIQUIÁTRICA",
            "OUTROS"
        ];
    </script>
</head>
<body>

    <div class="header-title">
        <div class="header-title-left">
            <i class="fa-solid fa-shield-halved fa-2x"></i>
            <div>
                <h2>SIGACTPAR - CONSELHO TUTELAR</h2>
                <span id="headerData">CARREGANDO DATA...</span>
            </div>
        </div>
        <div>
            <button type="button" class="btn-apagar-tudo" onclick="apagarTudo()">
                <i class="fa-solid fa-triangle-exclamation"></i> APAGAR TUDO
            </button>
        </div>
    </div>

    <!-- Abas de Navegação -->
    <div class="nav-tabs">
        <button type="button" class="tab-btn active" onclick="mudarAba(event, 'tabProcessos')">
            <i class="fa-solid fa-folder-open"></i> GESTÃO DE PROCESSOS E PASTAS
        </button>
        <button type="button" class="tab-btn" onclick="mudarAba(event, 'tabAtendimentos')">
            <i class="fa-solid fa-headset"></i> ATOS DE ATENDIMENTO
        </button>
    </div>

    <!-- ABA 1: PROCESSOS -->
    <div id="tabProcessos" class="tab-content active">
        
        <!-- Bloco de Edição / Cadastro -->
        <div class="container">
            <div id="editIndicator" class="edit-indicator" style="display: none;">
                <span><i class="fa-solid fa-pen-to-square"></i> EDITANDO REGISTRO DA PASTA: <strong id="editPastaDisplay"></strong></span>
                <button type="button" class="btn-cancelar-edicao" onclick="cancelarEdicao()">CANCELAR</button>
            </div>

            <form id="processoForm" onsubmit="event.preventDefault(); salvarRegistro();">
                <input type="hidden" id="editIndex" value="-1">
                <input type="hidden" id="pastaImportada" value="0">

                <div class="form-grid">
                    <div class="form-group small-field">
                        <label for="pasta"><i class="fa-solid fa-folder"></i> NÚMERO DA PASTA *</label>
                        <div style="display: flex; gap: 4px;">
                            <input type="text" id="pasta" required placeholder="Ex: A-1" oninput="onPastaInput()">
                            <button type="button" class="btn-add" onclick="gerarPastaAutomaticamente()" title="Gerar Pasta Automática"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                        </div>
                        <span class="pasta-hint"><button type="button" class="btn-gerar-pasta" onclick="gerarPastaAutomaticamente()">Gerar sequencial</button></span>
                    </div>

                    <div class="form-group small-field">
                        <label for="numProces"><i class="fa-solid fa-file-lines"></i> Nº PROCESSO / OFÍCIO</label>
                        <input type="text" id="numProces" placeholder="Ex: 001/2026">
                    </div>

                    <div class="form-group small-field">
                        <label for="tipoPessoa"><i class="fa-solid fa-user-tag"></i> TIPO</label>
                        <select id="tipoPessoa">
                            <option value="CRIANÇA">CRIANÇA / ADOLESCENTE</option>
                            <option value="FAMILIA">FAMÍLIA</option>
                        </select>
                    </div>
                </div>

                <!-- Crianças e Responsáveis Dinâmicos -->
                <div class="form-grid form-row-spacing">
                    <div class="form-group half-width">
                        <label><i class="fa-solid fa-child"></i> CRIANÇA(S) / ADOLESCENTE(S) (<span id="childCounter">0</span>)</label>
                        <div class="criancas-wrapper">
                            <div id="criancasContainer" class="criancas-scroll"></div>
                            <button type="button" class="btn-add" onclick="adicionarCampoCrianca()"><i class="fa-solid fa-plus"></i> ADICIONAR CRIANÇA</button>
                        </div>
                    </div>

                    <div class="form-group half-width">
                        <label><i class="fa-solid fa-user-shield"></i> RESPONSÁVEL / REPRESENTANTE</label>
                        <div class="responsaveis-wrapper">
                            <div id="responsaveisContainer" class="responsaveis-scroll">
                                <div class="dynamic-item">
                                    <input type="text" class="nomeResponsavel" placeholder="NOME COMPLETO">
                                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add" onclick="adicionarCampo('responsaveisContainer', 'nomeResponsavel', 'NOME COMPLETO')"><i class="fa-solid fa-plus"></i> ADICIONAR RESPONSÁVEL</button>
                        </div>
                    </div>
                </div>

                <div class="form-grid form-row-spacing">
                    <div class="form-group half-width">
                        <label for="endereco"><i class="fa-solid fa-location-dot"></i> ENDEREÇO</label>
                        <input type="text" id="endereco" placeholder="Endereço completo">
                    </div>

                    <div class="form-group small-field">
                        <label for="cidade"><i class="fa-solid fa-city"></i> REGIÃO / CIDADE</label>
                        <select id="cidade" onchange="toggleCidadeOutra()">
                            <option value="Paranoá" selected>Paranoá</option>
                            <option value="Itapoã">Itapoã</option>
                            <option value="Brasília (Plano Piloto)">Brasília (Plano Piloto)</option>
                            <option value="Outra">Outra região...</option>
                        </select>
                        <div id="cidadeOutraContainer" style="display: none; margin-top: 4px;">
                            <input type="text" id="cidadeOutraInput" placeholder="Digite a região/cidade">
                        </div>
                    </div>

                    <div class="form-group small-field" style="position: relative;">
                        <label for="assuntoInput"><i class="fa-solid fa-tag"></i> ASSUNTO / DEMANDA *</label>
                        <input type="text" id="assuntoInput" required autocomplete="off" placeholder="Digite ou selecione o assunto">
                        <div id="assuntoAutocompleteList" class="autocomplete-items" style="display:none;"></div>
                    </div>
                </div>

                <div class="form-grid form-row-spacing">
                    <div class="form-group small-field">
                        <label for="conselheiro"><i class="fa-solid fa-user-tie"></i> CONSELHEIRO RESPONSÁVEL</label>
                        <select id="conselheiro">
                            <option value="">Selecione...</option>
                            <option value="Conselheiro 1">Conselheiro 1</option>
                            <option value="Conselheiro 2">Conselheiro 2</option>
                            <option value="Conselheiro 3">Conselheiro 3</option>
                            <option value="Conselheiro 4">Conselheiro 4</option>
                            <option value="Conselheiro 5">Conselheiro 5</option>
                        </select>
                    </div>

                    <div class="form-group small-field">
                        <label for="processoSeiNum"><i class="fa-solid fa-barcode"></i> PROCESSO SEI</label>
                        <input type="text" id="processoSeiNum" placeholder="Nº do processo SEI">
                    </div>

                    <div class="form-group small-field">
                        <label for="observacaoProcesso"><i class="fa-solid fa-note-sticky"></i> OBSERVAÇÕES</label>
                        <input type="text" id="observacaoProcesso" placeholder="Observações pertinentes">
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" id="btnSalvarProcesso" class="btn-main btn-cadastrar">
                        <i class="fa-solid fa-floppy-disk"></i> <span id="btnSalvarTexto">CADASTRAR</span>
                    </button>
                    <button type="button" class="btn-main btn-excluir" onclick="tentarLimparFormulario()">
                        <i class="fa-solid fa-eraser"></i> LIMPAR
                    </button>
                </div>
            </form>
        </div>

        <!-- Botões de Controle e Dashboard -->
        <div style="display: flex; gap: 8px; margin-bottom: 10px;">
            <button type="button" class="btn-toggle-dash" onclick="toggleDashboard()">
                <i id="iconToggleDash" class="fa-solid fa-eye-slash"></i> <span id="textToggleDash">ESCONDER DASHBOARD</span>
            </button>
            <button type="button" class="btn-toggle-upload" onclick="toggleUpload()">
                <i id="iconToggleUpload" class="fa-solid fa-upload"></i> <span id="textToggleUpload">MOSTRAR IMPORTAÇÃO</span>
            </button>
        </div>

        <!-- Seção de Importação Planilha / Cole (Oculta por padrão) -->
        <div id="uploadSectionWrapper" class="upload-section" style="display: none;">
            <h3 style="font-size: 13px; color: var(--primary-color); margin-bottom: 8px;"><i class="fa-solid fa-file-excel"></i> IMPORTAR DADOS EM LOTE (EXCEL / CTRL+V)</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="margin-bottom: 4px;">Carregar arquivo Excel (.xlsx / .xls):</label>
                    <input type="file" id="excelFile" accept=".xlsx, .xls" onchange="processarPlanilha(event)" style="font-size: 12px; padding: 4px;">
                </div>
                <div>
                    <label style="margin-bottom: 4px;">Ou cole linhas copiadas do Excel (Tabulações):</label>
                    <div style="display: flex; gap: 6px;">
                        <textarea id="pasteInput" placeholder="Cole aqui..." style="height: 36px; resize: none;"></textarea>
                        <button type="button" class="btn-add" onclick="clicarColar()" style="background: var(--primary-color); color: white;">PROCESSAR</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Estatísticas (Ocultável) -->
        <div id="dashboardContainerWrap" class="dashboard-container-wrap">
            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <h3 style="font-size: 13px; color: var(--primary-color);"><i class="fa-solid fa-chart-pie"></i> DASHBOARD E ESTATÍSTICAS</h3>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <select id="filtroMes" onchange="atualizarDashboardEstatisticas()" style="padding: 4px; font-size: 11px;">
                            <option value="todos">Todos os Meses</option>
                            <option value="1">Janeiro</option><option value="2">Fevereiro</option><option value="3">Março</option>
                            <option value="4">Abril</option><option value="5">Maio</option><option value="6">Junho</option>
                            <option value="7">Julho</option><option value="8">Agosto</option><option value="9">Setembro</option>
                            <option value="10">Outubro</option><option value="11">Novembro</option><option value="12">Dezembro</option>
                        </select>
                        <select id="filtroAno" onchange="atualizarDashboardEstatisticas()" style="padding: 4px; font-size: 11px;">
                            <option value="2026" selected>2026</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                </div>
                <div class="dashboard-grid">
                    <div class="dash-box">
                        <h4>ASSUNTOS REGISTRADOS <span id="totalAssuntosCount" class="dash-badge">0</span></h4>
                        <ul id="listaAssuntosDash" class="dash-list"></ul>
                    </div>
                    <div class="dash-box">
                        <h4 id="regiaoDestaque">DESTAQUE: ---</h4>
                        <ul id="listaCidadesDash" class="dash-list"></ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Listagem de Processos -->
        <div class="container">
            <div class="table-header-flex">
                <h3><i class="fa-solid fa-table-list"></i> REGISTROS DE PROCESSOS E PASTAS</h3>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="text" id="inputPesquisaProcessos" placeholder="Pesquisar..." oninput="pesparProcessosInput()" style="padding: 4px 8px; font-size: 12px; width: 180px;">
                    <button type="button" class="btn-add" onclick="pesquisarProcessos()"><i class="fa-solid fa-search"></i></button>
                    <span id="contadorRegistros" class="dash-badge" style="font-size: 11px; padding: 4px 8px;">TOTAL: 0</span>
                </div>
            </div>
            <div class="table-container">
                <table id="tabelaRegistros">
                    <thead>
                        <tr>
                            <th>PASTA</th>
                            <th>Nº PROCESSO</th>
                            <th>CRIANÇA(S) / ADOLESCENTE(S)</th>
                            <th>NASC</th>
                            <th>RESPONSÁVEL</th>
                            <th>ENDEREÇO</th>
                            <th>REGIÃO</th>
                            <th>ASSUNTO</th>
                            <th>CONSELHEIRO</th>
                            <th>PROCESSO SEI</th>
                            <th>OBSERVAÇÃO</th>
                            <th style="text-align: center;">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRegistros">
                        <!-- Preenchido via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ABA 2: ATOS DE ATENDIMENTO -->
    <div id="tabAtendimentos" class="tab-content">
        <div class="container">
            <div id="editAtendimentoIndicator" class="edit-indicator" style="display: none;">
                <span><i class="fa-solid fa-pen-to-square"></i> EDITANDO ATENDIMENTO DE: <strong id="editAtendimentoDisplay"></strong></span>
                <button type="button" class="btn-cancelar-edicao" onclick="cancelarEdicaoAtendimento()">CANCELAR</button>
            </div>

            <form id="atendimentoForm" onsubmit="event.preventDefault(); salvarAtendimento();">
                <input type="hidden" id="editAtendimentoIndex" value="-1">

                <div class="form-grid">
                    <div class="form-group half-width">
                        <label for="atendNome"><i class="fa-solid fa-user"></i> NOME DA PESSOA ATENDIDA *</label>
                        <input type="text" id="atendNome" required placeholder="Nome completo">
                    </div>

                    <div class="form-group small-field">
                        <label for="atendCidade"><i class="fa-solid fa-city"></i> REGIÃO / CIDADE</label>
                        <select id="atendCidade" onchange="toggleAtendCidadeOutra()">
                            <option value="Paranoá" selected>Paranoá</option>
                            <option value="Itapoã">Itapoã</option>
                            <option value="Brasília (Plano Piloto)">Brasília (Plano Piloto)</option>
                            <option value="Outra">Outra região...</option>
                        </select>
                        <div id="atendCidadeOutraContainer" style="display: none; margin-top: 4px;">
                            <input type="text" id="atendCidadeOutraInput" placeholder="Digite a região/cidade">
                        </div>
                    </div>

                    <div class="form-group small-field" style="position: relative;">
                        <label for="atendAssuntoInput"><i class="fa-solid fa-tag"></i> ASSUNTO / MOTIVO *</label>
                        <input type="text" id="atendAssuntoInput" required autocomplete="off" placeholder="Assunto do atendimento">
                        <div id="atendAssuntoAutocompleteList" class="autocomplete-items" style="display:none;"></div>
                    </div>
                </div>

                <div class="form-grid form-row-spacing">
                    <div class="form-group small-field">
                        <label for="atendAdmin"><i class="fa-solid fa-user-gear"></i> ATENDENTE / ADMINISTRATIVO</label>
                        <select id="atendAdmin" onchange="toggleAdminOutro()">
                            <option value="Equipe Técnica" selected>Equipe Técnica</option>
                            <option value="Conselheiro Plantonista">Conselheiro Plantonista</option>
                            <option value="Secretaria">Secretaria</option>
                            <option value="Outro">Outro...</option>
                        </select>
                        <div id="atendAdminOutraContainer" style="display: none; margin-top: 4px;">
                            <input type="text" id="atendAdminOutraInput" placeholder="Nome do atendente">
                        </div>
                    </div>

                    <div class="form-group small-field">
                        <label for="atendDataHora"><i class="fa-solid fa-calendar-days"></i> DATA E HORA</label>
                        <input type="text" id="atendDataHora">
                    </div>

                    <div class="form-group half-width">
                        <label for="observacaoAtendimento"><i class="fa-solid fa-note-sticky"></i> RELATO / ENCAMINHAMENTO</label>
                        <textarea id="observacaoAtendimento" placeholder="Breve resumo do atendimento prestado" style="height: 38px; resize: vertical;"></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn-main btn-cadastrar">
                        <i class="fa-solid fa-floppy-disk"></i> <span id="btnSalvarAtendimentoTexto">SALVAR ATENDIMENTO</span>
                    </button>
                    <button type="button" class="btn-main btn-excluir" onclick="limparFormAtendimento()">
                        <i class="fa-solid fa-eraser"></i> LIMPAR
                    </button>
                </div>
            </form>
        </div>

        <!-- Estatísticas Rápidas de Atendimento -->
        <div class="container">
            <div style="display: flex; gap: 15px;">
                <div class="dash-box" style="flex: 1; text-align: center;">
                    <span style="font-size: 11px; color: var(--text-light);"><i class="fa-solid fa-headset"></i> TOTAL DE ATENDIMENTOS</span>
                    <h3 id="dashTotalAtendimentos" style="font-size: 20px; color: var(--primary-color);">0</h3>
                </div>
                <div class="dash-box" style="flex: 1; text-align: center;">
                    <span style="font-size: 11px; color: var(--text-light);"><i class="fa-solid fa-users"></i> PESSOAS ÚNICAS ATENDIDAS</span>
                    <h3 id="dashPessoasUnicas" style="font-size: 20px; color: var(--success-color);">0</h3>
                </div>
            </div>
        </div>

        <!-- Tabela de Atendimentos -->
        <div class="container">
            <div class="table-header-flex">
                <h3><i class="fa-solid fa-clipboard-user"></i> HISTÓRICO DE ATENDIMENTOS</h3>
                <span id="contadorAtendimentos" class="dash-badge" style="font-size: 11px; padding: 4px 8px;">TOTAL: 0</span>
            </div>
            <div class="table-container">
                <table id="tabelaAtendimentos">
                    <thead>
                        <tr>
                            <th>DATA/HORA</th>
                            <th>NOME</th>
                            <th>REGIÃO</th>
                            <th>ASSUNTO</th>
                            <th>ATENDENTE</th>
                            <th>RELATO / ENCAMINHAMENTO</th>
                            <th style="text-align: center;">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAtendimentos">
                        <!-- Preenchido via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DE HISTÓRICO DE SERVIÇOS DA PASTA -->
    <div id="modalHistorico" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 2px solid var(--bg-color); padding-bottom: 8px;">
                <h3 id="modalTituloPasta" style="font-size: 14px; color: var(--primary-color);"><i class="fa-solid fa-clock-rotate-left"></i> HISTÓRICO</h3>
                <button type="button" class="btn-remove" onclick="fecharModalHistorico()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <input type="hidden" id="historicoIndexAtual">

            <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-bottom: 12px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 11px; color: var(--primary-color); margin-bottom: 6px;">ADICIONAR NOVO REGISTRO / SERVIÇO NA PASTA</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                    <div>
                        <label style="font-size: 10px;">Data do Serviço:</label>
                        <input type="date" id="novoServicoData" style="font-size: 12px; padding: 6px;" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label style="font-size: 10px;">Conselheiro:</label>
                        <input type="text" id="novoServicoConselheiro" placeholder="Nome" style="font-size: 12px; padding: 6px;">
                    </div>
                </div>
                <div style="margin-bottom: 8px;">
                    <label style="font-size: 10px;">Descrição da Ação / Diligência:</label>
                    <textarea id="novoServicoDescricao" placeholder="Descreva o atendimento, visita ou encaminhamento..." style="height: 50px; font-size: 12px; resize: none;"></textarea>
                </div>
                <button type="button" class="btn-add" onclick="adicionarNovoServicoNaPasta()" style="background: var(--success-color); color: white; width: 100%; justify-content: center;">
                    <i class="fa-solid fa-plus"></i> ADICIONAR AO HISTÓRICO DA PASTA
                </button>
            </div>

            <div class="table-container" style="max-height: 200px;">
                <table id="tabelaHistoricoServicos">
                    <thead>
                        <tr>
                            <th>DATA</th>
                            <th>CONSELHEIRO</th>
                            <th>DESCRIÇÃO</th>
                            <th style="text-align: center;">AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dinâmico -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Script Principal -->
    <script src="js/script.js"></script>
    <script>
        function mudarAba(evt, tabId) {
            const contents = document.querySelectorAll(".tab-content");
            contents.forEach(c => c.classList.remove("active"));

            const buttons = document.querySelectorAll(".tab-btn");
            buttons.forEach(b => b.classList.remove("active"));

            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function pesparProcessosInput() {
            // Atalho dinâmico opcional de busca
        }
    </script>
</body>
</html>
