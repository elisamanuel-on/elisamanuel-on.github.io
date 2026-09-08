# ============================================================
# Limpeza do Portfólio — Elisama Manuel
# Corre este script a partir do PowerShell, dentro da pasta do site.
# Nenhum destes passos é um bug — é só arrumação de ficheiros.
# ============================================================

cd "C:\Users\elisa\Documents\HTML\PORTFOLIO - CV\HTML"

Write-Host "A remover PDF do CV duplicado (cv\Elisama-Manuel-CV_1.pdf)..."
Remove-Item "cv\Elisama-Manuel-CV_1.pdf" -ErrorAction SilentlyContinue

Write-Host "A remover imagem WhatsApp de maio que já não é usada..."
Remove-Item "imagens\WhatsApp Image 2026-05-06 at 16.23.35 (1).jpeg" -ErrorAction SilentlyContinue

Write-Host "A remover .vscode do controlo de versões (fica só no teu PC, sai do Git)..."
git rm -r --cached "projetos\EXERCICIO008 - Tabuada\.vscode"
Add-Content .gitignore "`n.vscode/"
git add .gitignore
git commit -m "Limpeza: remove CV duplicado, imagem orfa e .vscode do repo"
git push

Write-Host "Feito!"
