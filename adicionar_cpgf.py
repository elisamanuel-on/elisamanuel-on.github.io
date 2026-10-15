"""
Adiciona o projeto CPGF Pipeline ao portfolio (PT / EN / ES / FR).

Como usar:
  1. Copia este ficheiro e o cpgf-card.png para a pasta do portfolio (a que tem o script.js).
  2. Nessa pasta, corre:  python adicionar_cpgf.py
  3. Abre o portfolio.html no navegador para ver. Se estiver bem: git add . / git commit / git push

O que faz:
  - script.js: novo projeto 029 e nova secção "Dados e automação"; o botão do cartão
    diz "Ver no GitHub" quando o link é do GitHub.
  - i18n.js: texto do botão nos 4 idiomas.
  - portfolio.html: "17 projetos" passa a "18 projetos".
  - todas as páginas .html: muda o número ?v= para o navegador não usar ficheiros antigos.
  - copia o cpgf-card.png para imagens/029-cpgf-pipeline.png.
  - antes de mexer, guarda uma cópia de cada ficheiro com a terminação .bak-cpgf.
Se alguma das âncoras não for encontrada, não escreve nada e diz qual falhou.
"""
import re
import shutil
import sys
from pathlib import Path

PASTA = Path(__file__).resolve().parent
VERSAO_ANTIGA = "2026.10.04-21"
VERSAO_NOVA = "2026.10.15-1"
GITHUB_URL = "https://github.com/elisamanuel-on/cpgf-pipeline"

NOVO_PROJETO = """    {
        id: '029',
        grupo: 'dados',
        titulo: {
            pt: 'CPGF Pipeline: Gastos com Cartões do Governo Federal',
            en: 'CPGF Pipeline: Federal Government Card Spending',
            es: 'CPGF Pipeline: Gastos con Tarjetas del Gobierno Federal',
            fr: 'CPGF Pipeline : Dépenses par Cartes du Gouvernement Fédéral'
        },
        descricao: {
            pt: 'Pipeline de dados que descarrega do Portal da Transparência do Brasil os gastos com cartões do Governo Federal (2022), trata 136 mil registos com pandas, importa em lotes para MySQL/MariaDB sem duplicar e responde com SQL a perguntas sobre os gastos. Um só comando corre tudo em cerca de 46 segundos, com testes automáticos. A análise mostra que quase metade do valor (90,3 milhões de reais) está sob sigilo',
            en: 'Data pipeline that downloads Brazil\\'s Federal Government card spending (2022) from the Transparency Portal, cleans 136 thousand records with pandas, loads them in batches into MySQL/MariaDB without duplicates and answers questions about the spending with SQL. A single command runs everything in about 46 seconds, with automated tests. The analysis shows that almost half of the amount (90.3 million reais) is classified as secret',
            es: 'Pipeline de datos que descarga del Portal de la Transparencia de Brasil los gastos con tarjetas del Gobierno Federal (2022), trata 136 mil registros con pandas, los importa por lotes a MySQL/MariaDB sin duplicar y responde con SQL a preguntas sobre los gastos. Un solo comando lo ejecuta todo en unos 46 segundos, con pruebas automáticas. El análisis muestra que casi la mitad del valor (90,3 millones de reales) está bajo secreto',
            fr: 'Pipeline de données qui télécharge du Portail de la Transparence du Brésil les dépenses par cartes du Gouvernement Fédéral (2022), nettoie 136 mille enregistrements avec pandas, les charge par lots dans MySQL/MariaDB sans doublons et répond en SQL à des questions sur les dépenses. Une seule commande exécute tout en environ 46 secondes, avec des tests automatisés. L\\'analyse montre que près de la moitié du montant (90,3 millions de reais) est classée secrète'
        },
        tag: 'Python',
        tecnologias: ['Python', 'pandas', 'MySQL', 'MariaDB', 'SQL', 'pytest', 'Git'],
        url: '__GITHUB__',
        imagem: 'imagens/029-cpgf-pipeline.png'
    }""".replace("__GITHUB__", GITHUB_URL)

NOVO_GRUPO = """    {
        id: 'dados',
        titulo: { pt: 'Dados e automação', en: 'Data and automation', es: 'Datos y automatización', fr: 'Données et automatisation' },
        descricao: {
            pt: 'Pipelines de dados reais, bases de dados e análise com SQL, com o código no GitHub',
            en: 'Real-data pipelines, databases and SQL analysis, with the code on GitHub',
            es: 'Pipelines de datos reales, bases de datos y análisis con SQL, con el código en GitHub',
            fr: 'Pipelines de données réelles, bases de données et analyse SQL, avec le code sur GitHub'
        }
    },
"""

BOTAO_ANTIGO = "<span class=\"projeto-link\">${t('projeto_ver')}</span>"
BOTAO_NOVO = ("<span class=\"projeto-link\">${(proj.url && proj.url.includes('github.com')) "
              "? t('projeto_ver_codigo') : t('projeto_ver')}</span>")

TEXTO_BOTAO = ["Ver no GitHub", "View on GitHub", "Ver en GitHub", "Voir sur GitHub"]  # pt, en, es, fr


def ler(caminho):
    bruto = caminho.read_bytes().decode("utf-8")
    return bruto.replace("\r\n", "\n"), "\r\n" in bruto


def escrever(caminho, texto, crlf):
    if crlf:
        texto = texto.replace("\n", "\r\n")
    caminho.write_bytes(texto.encode("utf-8"))


def falhar(msg):
    print("\nERRO: " + msg)
    print("Nada foi alterado. Envia-me esta mensagem e o ficheiro em causa.")
    sys.exit(1)


def preparar_script_js(texto):
    if "id: '029'" in texto:
        print("script.js: já tem o projeto 029, não mexi.")
        return texto
    # 1) novo projeto no fim da lista
    padrao = re.compile(r"\n    \}\n\];\n(\s*)// Grupos em que os projetos aparecem")
    if len(padrao.findall(texto)) != 1:
        falhar("script.js: não encontrei o fim da lista 'projetos' (seguido de '// Grupos em que os projetos aparecem').")
    texto = padrao.sub(lambda m: "\n    },\n" + NOVO_PROJETO + "\n];\n" + m.group(1) + "// Grupos em que os projetos aparecem", texto)
    # 2) novo grupo, logo antes de 'Sistemas e jogos web'
    ancora = "    {\n        id: 'web',\n        titulo: { pt: 'Sistemas e jogos web'"
    if texto.count(ancora) != 1:
        falhar("script.js: não encontrei o grupo 'Sistemas e jogos web' em gruposProjetos.")
    texto = texto.replace(ancora, NOVO_GRUPO + ancora)
    # 3) texto do botão
    if texto.count(BOTAO_ANTIGO) != 1:
        falhar("script.js: não encontrei a linha do botão <span class=\"projeto-link\">.")
    texto = texto.replace(BOTAO_ANTIGO, BOTAO_NOVO)
    return texto


def preparar_i18n(texto):
    if "projeto_ver_codigo" in texto:
        print("i18n.js: já tem 'projeto_ver_codigo', não mexi.")
        return texto
    padrao = re.compile(r"^(\s*)projeto_ver: .*,$", re.M)
    achados = padrao.findall(texto)
    if len(achados) != 4:
        falhar("i18n.js: esperava 4 linhas 'projeto_ver:' (pt, en, es, fr) e encontrei %d." % len(achados))
    idx = iter(range(4))
    return padrao.sub(
        lambda m: m.group(0) + "\n" + m.group(1) + "projeto_ver_codigo: '" + TEXTO_BOTAO[next(idx)] + "',",
        texto,
    )


def main():
    script_js = PASTA / "script.js"
    i18n_js = PASTA / "i18n.js"
    portfolio_html = PASTA / "portfolio.html"
    for f in (script_js, i18n_js, portfolio_html):
        if not f.exists():
            falhar("não encontrei %s. Corre o script dentro da pasta do portfolio." % f.name)

    alteracoes = {}  # caminho -> (texto novo, crlf)

    txt, crlf = ler(script_js)
    alteracoes[script_js] = (preparar_script_js(txt), crlf)

    txt, crlf = ler(i18n_js)
    alteracoes[i18n_js] = (preparar_i18n(txt), crlf)

    # páginas HTML: versão da cache e contador de projetos
    for html in sorted(PASTA.glob("*.html")):
        txt, crlf = ler(html)
        novo = txt.replace(VERSAO_ANTIGA, VERSAO_NOVA)
        if html.name == "portfolio.html":
            novo = novo.replace("17 projetos práticos", "18 projetos práticos")
        if novo != txt:
            alteracoes[html] = (novo, crlf)

    # tudo certo: guardar cópias e escrever
    for caminho, (novo, crlf) in alteracoes.items():
        original, _ = ler(caminho)
        if novo == original:
            continue
        copia = caminho.with_name(caminho.name + ".bak-cpgf")
        if not copia.exists():
            shutil.copy2(caminho, copia)
        escrever(caminho, novo, crlf)
        print("atualizado:", caminho.name)

    # imagem do cartão
    origem = PASTA / "cpgf-card.png"
    destino = PASTA / "imagens" / "029-cpgf-pipeline.png"
    if destino.exists():
        print("imagem: já existe imagens/029-cpgf-pipeline.png")
    elif origem.exists() and destino.parent.exists():
        shutil.copy2(origem, destino)
        print("imagem copiada para imagens/029-cpgf-pipeline.png")
    else:
        print("AVISO: falta copiar o cpgf-card.png para imagens/029-cpgf-pipeline.png")

    # avisos de números que o script não mexe
    for f in list(PASTA.glob("*.html")) + list(PASTA.glob("*.js")):
        if f.name.startswith("adicionar_"):
            continue
        t, _ = ler(f)
        if re.search(r"\b17\s+(projetos|projects|proyectos|projets)\b", t, re.I):
            print("VERIFICAR: %s ainda diz '17 projetos' (ou equivalente)." % f.name)

    print("\nFeito. Abre o portfolio.html no navegador (Ctrl+F5) e confirma o cartão nos 4 idiomas.")


if __name__ == "__main__":
    main()
