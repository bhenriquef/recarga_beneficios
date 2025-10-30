import "./bootstrap";
import Alpine from "alpinejs";

window.Alpine = Alpine;

// Componente do modal
window.exportModal = function () {
    return {
        openModal: false,
        mensagem: "Verificando arquivos...",
        links: { ifood: null, vr: null },

        open() {
            this.openModal = true;
            this.mensagem = "Verificando arquivos...";
            this.links = { ifood: null, vr: null };
            this.verificarArquivos();
        },

        close() {
            this.openModal = false;
        },

        async verificarArquivos() {
            try {
                const res = await fetch("/exports/check", {
                    credentials: "same-origin",
                });
                if (!res.ok) throw new Error("Falha ao verificar arquivos");
                const data = await res.json();

                this.links.ifood = data?.urls?.ifood || null;
                this.links.vr = data?.urls?.vr || null;

                if (this.links.ifood || this.links.vr) {
                    this.mensagem =
                        "Arquivos deste mês já existem. Deseja baixar ou gerar novos?";
                } else {
                    this.mensagem =
                        "Nenhum arquivo encontrado para este mês. Deseja gerar novos?";
                }
            } catch (e) {
                this.mensagem = "Erro ao verificar arquivos. Tente novamente.";
                console.error(e);
            }
        },

        async baixar(tipo) {
            try {
                const res = await fetch(`/exports/download/${tipo}`, {
                    credentials: "same-origin",
                });
                if (!res.ok) throw new Error("Falha no download");

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `planilha_${tipo}_${new Date()
                    .toISOString()
                    .slice(0, 7)}.xlsx`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            } catch (e) {
                alert("Não foi possível baixar o arquivo.");
                console.error(e);
            }
        },

        async gerarNovos() {
            try {
                const res = await fetch("/exports/generate", {
                    credentials: "same-origin",
                });
                if (!res.ok) throw new Error("Erro ao gerar novos arquivos");
                this.mensagem = "Arquivos gerados com sucesso! Atualizando...";
                await this.verificarArquivos();
            } catch (e) {
                alert("Erro ao gerar novos arquivos.");
                console.error(e);
            }
        },
    };
};

Alpine.start();
