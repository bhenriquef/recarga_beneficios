export default function exportModal() {
    return {
        isOpen: false,
        loading: false,
        mensagem: "Verificando arquivos...",
        links: { ifood: null, vr: null },

        show() {
            this.isOpen = true;
            this.mensagem = "Verificando arquivos...";
            this.links = { ifood: null, vr: null };
            this.check();
        },
        hide() {
            this.isOpen = false;
        },

        async check() {
            try {
                this.loading = true;
                const res = await fetch("/exports/check", {
                    credentials: "same-origin",
                });
                if (!res.ok) throw new Error("Falha ao verificar arquivos");
                const data = await res.json();
                this.links.ifood = data?.urls?.ifood || null;
                this.links.vr = data?.urls?.vr || null;
                this.mensagem =
                    this.links.ifood || this.links.vr
                        ? "Arquivos deste mês já existem. Deseja baixar ou gerar novos?"
                        : "Nenhum arquivo encontrado para este mês. Deseja gerar novos?";
            } catch (e) {
                console.error(e);
                this.mensagem = "Erro ao verificar arquivos. Tente novamente.";
            } finally {
                this.loading = false;
            }
        },

        async download(tipo) {
            try {
                this.loading = true;
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
            } finally {
                this.loading = false;
            }
        },

        async generate() {
            try {
                this.loading = true;
                const res = await fetch("/exports/generate", {
                    credentials: "same-origin",
                });
                if (!res.ok) throw new Error("Erro ao gerar novos arquivos");
                const data = await res.json();

                this.mensagem =
                    data?.Success ||
                    "Arquivos gerados com sucesso! Atualizando...";

                window.dispatchEvent(
                    new CustomEvent("notify", {
                        detail: {
                            type: "success",
                            message:
                                data?.Success || "Excel gerado com sucesso!",
                        },
                    })
                );

                await this.check();
            } catch (e) {
                console.error(e);

                window.dispatchEvent(
                    new CustomEvent("notify", {
                        detail: {
                            type: "error",
                            message: "Erro ao gerar novos arquivos.",
                        },
                    })
                );

                this.mensagem =
                    "Erro ao gerar novos arquivos. Tente novamente.";
            } finally {
                this.loading = false;
            }
        },
    };
}
