function abrirAnalisador() {
    window.location.href = 'analisador.php';
}

function verDetalhesGreen(id) {
    fetch(`../modules/greens_logic.php?acao=detalhes_green&id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modal-titulo').innerText = data.titulo;
            document.getElementById('modal-img1').src = data.url_foto_topo;
            document.getElementById('modal-texto').innerText = data.texto_completo;
            document.getElementById('modal-green').style.display = 'block';
        });
}

function fecharModal() {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}