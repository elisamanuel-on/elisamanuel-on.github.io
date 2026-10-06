// Conteúdo do Quiz Dev (temas, fichas e perguntas). Para acrescentar um tema, junta um objeto igual a este à lista.
window.QUIZ_DADOS = [
 {
  "id": "fundamentos",
  "nome": "Fundamentos de programação",
  "cor": "#a78bfa",
  "descricao": "Conceitos base, com exemplos em Python",
  "topicos": [
   {
    "id": "variaveis",
    "nome": "Variáveis e tipos de dados",
    "ficheiro": "variaveis.py",
    "ficha": {
     "oque": "Uma variável é um nome que guarda um valor na memória do programa. Em Python não declaras o tipo: ele vem do valor que atribuis com o operador =. Os tipos base são int, float, str, bool, list e dict.",
     "pontos": [
      [
       "int e float",
       "int são números inteiros (7); float são números decimais, escritos com ponto (3.5)."
      ],
      [
       "str e bool",
       "str é texto entre aspas ('olá'); bool só tem dois valores: True e False (com maiúscula)."
      ],
      [
       "list e dict",
       "list é uma sequência ordenada acedida por índice a partir de 0 ([10, 20]); dict guarda pares chave-valor ({'nome': 'Ana'})."
      ],
      [
       "type(x)",
       "Devolve o tipo do valor de x, por exemplo <class 'int'>."
      ],
      [
       "int(), str()",
       "Convertem um valor para outro tipo: int('5') dá 5 e str(5) dá '5'."
      ],
      [
       "f-string",
       "f\"Olá {nome}\" insere o valor de expressões dentro do texto; o f antes das aspas é obrigatório."
      ]
     ],
     "exemplo": "nome = \"Ana\"\nidade = 20\nprint(type(idade))\nprint(f\"{nome} tem {idade} anos\")\n# <class 'int'>\n# Ana tem 20 anos",
     "dica": "input() devolve sempre str: converte com int() antes de fazer contas, porque \"5\" + 3 dá TypeError.",
     "fonte": "Documentação oficial do Python (docs.python.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "O que é uma variável em Python?",
        "o": [
         "Um comentário que o Python ignora",
         "Uma função pré-definida da linguagem",
         "Um nome que se refere a um valor guardado pelo programa",
         "Um ficheiro onde o programa grava dados"
        ],
        "c": 2,
        "e": "Atribuis um valor a um nome (idade = 20) e passas a usar o nome para ler ou alterar esse valor. Não é um ficheiro nem uma função: é só uma etiqueta para um valor em memória."
       },
       {
        "t": "mc",
        "q": "Qual destes valores é do tipo float?",
        "o": [
         "7.5",
         "7",
         "'7.5'",
         "[7.5]"
        ],
        "c": 0,
        "e": "Um float é um número com parte decimal, escrito com ponto. 7 é int, '7.5' é str (está entre aspas) e [7.5] é uma list com um elemento."
       },
       {
        "t": "mc",
        "q": "Que tipo de dados do Python guarda pares chave-valor?",
        "o": [
         "list",
         "dict",
         "str",
         "bool"
        ],
        "c": 1,
        "e": "Um dict (dicionário) associa cada chave a um valor, como {'nome': 'Ana'}. Uma list só tem posições numeradas a partir de 0, sem chaves."
       },
       {
        "t": "mc",
        "q": "Para que serve a função type()?",
        "o": [
         "Converte um valor para texto",
         "Define o tipo de uma variável antes de a usar",
         "Pede ao utilizador que escreva um valor",
         "Devolve o tipo do valor que lhe passas"
        ],
        "c": 3,
        "e": "type(x) diz-te que tipo tem o valor de x (int, str, list...). Em Python o tipo está no valor, não na variável, e por isso não se declara antes de usar."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que mostra este código?",
        "cod": "idade = 20\nidade = idade + 1\nprint(idade)",
        "o": [
         "20",
         "21",
         "idade + 1",
         "Dá erro"
        ],
        "c": 1,
        "e": "Na atribuição, o lado direito é calculado primeiro (20 + 1 = 21) e só depois o resultado fica guardado no nome idade, substituindo o valor antigo."
       },
       {
        "t": "cod",
        "q": "Qual é o resultado de executar este código?",
        "cod": "x = \"5\"\ny = 3\nprint(int(x) + y)",
        "o": [
         "53",
         "Dá erro (TypeError)",
         "5 + 3",
         "8"
        ],
        "c": 3,
        "e": "int(x) converte o texto \"5\" no número 5, e 5 + 3 dá 8. Sem a conversão, \"5\" + 3 daria TypeError, porque o Python não soma str com int."
       },
       {
        "t": "cod",
        "q": "O que aparece no ecrã quando corres isto?",
        "cod": "nome = \"Rui\"\nidade = 19\nprint(f\"{nome} tem {idade + 1} anos\")",
        "o": [
         "Rui tem 20 anos",
         "Rui tem 19 anos",
         "nome tem idade + 1 anos",
         "Rui tem {idade + 1} anos"
        ],
        "c": 0,
        "e": "Numa f-string, tudo o que está entre chavetas é calculado e substituído pelo resultado. Aqui idade + 1 vale 20."
       },
       {
        "t": "cod",
        "q": "Qual é a saída deste código?",
        "cod": "a = 7\nb = 2\nprint(a / b, a // b, a % b)",
        "o": [
         "3 3 1",
         "3.5 3.0 1",
         "3.5 3 1",
         "3.5 4 1"
        ],
        "c": 2,
        "e": "/ é a divisão normal e devolve sempre float (3.5); // é a divisão inteira (3) e % é o resto da divisão (1)."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "Onde está o erro neste código?",
        "cod": "idade = 20\nprint(\"Tenho \" + idade + \" anos\")",
        "o": [
         "Falta converter idade com str(): o + não junta texto com um número",
         "A variável devia ser declarada com var",
         "O print precisa de ponto e vírgula no fim",
         "O número 20 devia estar entre aspas na atribuição"
        ],
        "c": 0,
        "e": "Juntar str com int usando + dá TypeError. Podes usar str(idade) ou, melhor, uma f-string: f\"Tenho {idade} anos\"."
       },
       {
        "t": "lac",
        "q": "Completa o código para mostrar o tipo da variável.",
        "cod": "preco = 12.5\nprint(___(preco))",
        "o": [
         "len",
         "str",
         "typeof",
         "type"
        ],
        "c": 3,
        "e": "type(preco) devolve <class 'float'>. len() dá o comprimento de sequências (e falharia com um número) e typeof é do JavaScript, não existe em Python."
       },
       {
        "t": "cod",
        "q": "Este código dá erro. Porquê?",
        "cod": "precos = [10, 20, 30]\nprint(precos[3])",
        "o": [
         "As listas só aceitam texto",
         "precos[3] devolve 30, mas falta convertê-lo para str",
         "Os índices começam em 0: o último elemento é precos[2], e precos[3] não existe (IndexError)",
         "O índice 3 só funciona com dicionários"
        ],
        "c": 2,
        "e": "Uma lista com 3 elementos tem índices 0, 1 e 2. Pedir o índice 3 fora do limite dá IndexError."
       },
       {
        "t": "lac",
        "q": "Completa o código para o resultado ser 21.",
        "cod": "idade = \"20\"\nproximo = ___(idade) + 1\nprint(proximo)",
        "o": [
         "str",
         "int",
         "len",
         "bool"
        ],
        "c": 1,
        "e": "idade guarda o texto \"20\"; int(idade) converte-o no número 20, que já se pode somar com 1. Com str() continuarias com texto e o + daria TypeError."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha que guarda o número 20 na variável idade.",
        "ok": [
         "idade = 20",
         "idade=20"
        ],
        "tok": [
         "idade",
         "=",
         "20",
         "==",
         "19"
        ],
        "e": "Atribui-se com um só =. O == serve para comparar valores e não guarda nada."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que guarda na variável tipo o resultado de type(idade).",
        "ok": [
         "tipo = type(idade)",
         "tipo=type(idade)"
        ],
        "tok": [
         "tipo",
         "=",
         "type(idade)",
         "==",
         "type(tipo)",
         "typeof(idade)"
        ],
        "e": "O lado direito, type(idade), é calculado primeiro e o resultado fica guardado em tipo."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que converte o texto da variável texto para inteiro e guarda o resultado em n.",
        "ok": [
         "n = int(texto)",
         "n=int(texto)"
        ],
        "tok": [
         "n",
         "=",
         "int(texto)",
         "str(texto)",
         "float(texto)",
         "=="
        ],
        "e": "int() converte o texto num número inteiro (por exemplo int('42') dá 42). float() daria um decimal e str() devolveria texto."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que guarda na variável msg uma f-string que contém só o valor de nome seguido de '!'.",
        "ok": [
         "msg = f'{nome}!'",
         "msg=f'{nome}!'"
        ],
        "tok": [
         "msg",
         "=",
         "f'{nome}!'",
         "'{nome}!'",
         "==",
         "f'nome!'"
        ],
        "e": "O f antes das aspas ativa a substituição: {nome} é trocado pelo valor da variável. Sem o f, o texto ficaria literalmente '{nome}!'."
       }
      ]
     }
    ]
   },
   {
    "id": "condicoes",
    "nome": "Condições e ciclos",
    "ficheiro": "condicoes.py",
    "ficha": {
     "oque": "As condições (if) deixam o programa escolher que código executar, consoante uma expressão seja True ou False. Os ciclos repetem código: for percorre uma sequência (por exemplo range) e while repete enquanto a condição for verdadeira. Em Python, cada bloco é marcado pela indentação.",
     "pontos": [
      [
       "if / elif / else",
       "As condições são testadas por ordem e só corre o primeiro bloco cuja condição é True; else apanha o resto."
      ],
      [
       "== != < <= > >=",
       "Comparações que dão True ou False. Atenção: = atribui e == compara."
      ],
      [
       "and, or, not",
       "Combinam condições: and (as duas), or (pelo menos uma) e not (inverte)."
      ],
      [
       "for i in range(n)",
       "Repete n vezes com i = 0, 1, ..., n-1; range(a, b) vai de a até b-1."
      ],
      [
       "while condição:",
       "Repete enquanto a condição for True; alguma coisa dentro do ciclo tem de a tornar False."
      ],
      [
       "break e continue",
       "break sai do ciclo; continue salta para a iteração seguinte."
      ]
     ],
     "exemplo": "for i in range(1, 6):\n    if i == 3:\n        continue\n    if i == 5:\n        break\n    print(i)\n# escreve 1, 2 e 4",
     "dica": "Esquecer os dois pontos no fim da linha, ou escrever = em vez de == dentro de um if, dá SyntaxError. Um while que nunca altera a condição fica infinito.",
     "fonte": "Documentação oficial do Python (docs.python.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Para que serve o elif?",
        "o": [
         "Repetir um bloco várias vezes",
         "Terminar o programa",
         "Declarar uma variável",
         "Testar outra condição quando as anteriores do if foram falsas"
        ],
        "c": 3,
        "e": "elif é a junção de else com if: só é avaliado se o if (e os elif anteriores) deram False. Assim o programa escolhe um único caminho entre vários."
       },
       {
        "t": "mc",
        "q": "Qual operador verifica se dois valores são iguais?",
        "o": [
         "=",
         "==",
         "=>",
         "!="
        ],
        "c": 1,
        "e": "== compara e devolve True ou False. O = faz atribuição, != verifica se são diferentes e => nem existe em Python."
       },
       {
        "t": "mc",
        "q": "O que faz a instrução break dentro de um ciclo?",
        "o": [
         "Termina o ciclo imediatamente",
         "Salta só esta iteração e continua no ciclo",
         "Reinicia o ciclo desde o princípio",
         "Termina o programa inteiro"
        ],
        "c": 0,
        "e": "break sai do ciclo mais interior onde está e o programa segue na linha a seguir a ele. Quem salta só a iteração actual é o continue."
       },
       {
        "t": "mc",
        "q": "Quando é que um while é mais adequado do que um for com range?",
        "o": [
         "Quando queres repetir exactamente 10 vezes",
         "Quando o ciclo não precisa de condição",
         "Quando não sabes quantas repetições serão precisas e repetes enquanto uma condição for verdadeira",
         "Nunca, o for faz sempre tudo melhor"
        ],
        "c": 2,
        "e": "O for com range é ideal para um número conhecido de repetições. O while serve quando a paragem depende de uma condição, como esperar que o utilizador escreva uma resposta válida."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que mostra este código?",
        "cod": "x = 7\nif x > 10:\n    print(\"grande\")\nelif x > 5:\n    print(\"médio\")\nelse:\n    print(\"pequeno\")",
        "o": [
         "grande",
         "médio",
         "pequeno",
         "grande e médio"
        ],
        "c": 1,
        "e": "x > 10 é False, por isso passa ao elif: x > 5 é True e escreve \"médio\". Depois de um bloco correr, o resto da cadeia é ignorado."
       },
       {
        "t": "cod",
        "q": "Qual é o resultado de executar este código?",
        "cod": "for i in range(3):\n    print(i)",
        "o": [
         "1, 2 e 3 (um por linha)",
         "0, 1, 2 e 3 (um por linha)",
         "Só o número 3",
         "0, 1 e 2 (um por linha)"
        ],
        "c": 3,
        "e": "range(3) gera 0, 1 e 2: começa em 0 e pára antes de chegar ao 3."
       },
       {
        "t": "cod",
        "q": "O que aparece no ecrã quando corres isto?",
        "cod": "n = 1\nwhile n < 20:\n    n = n * 2\nprint(n)",
        "o": [
         "32",
         "16",
         "20",
         "64"
        ],
        "c": 0,
        "e": "n vai valendo 1, 2, 4, 8, 16 (ainda menor do que 20, por isso dobra outra vez) e 32. Com 32 a condição é False, o ciclo acaba e o print mostra 32."
       },
       {
        "t": "cod",
        "q": "Qual é a saída deste código?",
        "cod": "total = 0\nfor i in range(1, 6):\n    if i % 2 == 0:\n        continue\n    total += i\nprint(total)",
        "o": [
         "15",
         "6",
         "9",
         "12"
        ],
        "c": 2,
        "e": "range(1, 6) dá 1 a 5. Os pares (i % 2 == 0) são saltados pelo continue, por isso soma só 1 + 3 + 5 = 9."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "Onde está o erro neste código?",
        "cod": "nota = 15\nif nota = 10:\n    print(\"positiva\")",
        "o": [
         "Falta um else no fim",
         "Usa = (atribuição) em vez de == para comparar",
         "A variável nota devia ser texto",
         "Falta um ponto e vírgula no fim da linha do if"
        ],
        "c": 1,
        "e": "Dentro de um if é preciso uma comparação (==, >=, ...). Escrever só = é uma atribuição e dá SyntaxError."
       },
       {
        "t": "lac",
        "q": "Completa o código para escrever \"maior\" apenas quando a idade for pelo menos 18.",
        "cod": "idade = 20\nif idade ___ 18:\n    print(\"maior\")\nelse:\n    print(\"menor\")",
        "o": [
         ">=",
         "<=",
         "<",
         "=="
        ],
        "c": 0,
        "e": ">= significa \"maior ou igual\", por isso 18 também conta. Com == só o 18 exacto passaria e com <= ou < o resultado seria o contrário."
       },
       {
        "t": "cod",
        "q": "Este ciclo nunca termina. Qual é a correcção para escrever 0, 1 e 2 e parar?",
        "cod": "contador = 0\nwhile contador < 3:\n    print(contador)",
        "o": [
         "Trocar while por if",
         "Mudar < 3 para > 3",
         "Acrescentar contador += 1 dentro do ciclo",
         "Escrever contador = 0 dentro do ciclo"
        ],
        "c": 2,
        "e": "Como contador nunca muda, contador < 3 é sempre True. Somar 1 em cada volta faz a condição tornar-se False quando chega a 3."
       },
       {
        "t": "lac",
        "q": "Completa o código para repetir 4 vezes.",
        "cod": "for i in ___(4):\n    print(i)",
        "o": [
         "len",
         "range",
         "list",
         "count"
        ],
        "c": 1,
        "e": "range(4) produz 0, 1, 2 e 3, ou seja, 4 voltas. len() e count() não geram sequências de números e list(4) dá TypeError."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha de abertura de um ciclo for com a variável i que repete 5 vezes, usando range.",
        "ok": [
         "for i in range(5):"
        ],
        "tok": [
         "for",
         "i",
         "in",
         "range(5):",
         "while",
         "range(6):",
         "if"
        ],
        "e": "for i in range(5): dá i = 0, 1, 2, 3, 4, ou seja, 5 voltas. Não esquecer os dois pontos no fim."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de abertura de um ciclo while que repete enquanto contador for menor do que 10.",
        "ok": [
         "while contador < 10:"
        ],
        "tok": [
         "while",
         "contador",
         "<",
         "10:",
         "if",
         ">",
         "<="
        ],
        "e": "O while volta a testar a condição antes de cada repetição e pára quando contador < 10 for False."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de abertura de um if que testa se idade é maior do que 12 e menor do que 18, ligando as duas comparações com and.",
        "ok": [
         "if idade > 12 and idade < 18:"
        ],
        "tok": [
         "if",
         "idade",
         ">",
         "12",
         "and",
         "idade",
         "<",
         "18:",
         "or",
         "not"
        ],
        "e": "O and só dá True quando as duas comparações são verdadeiras. Com or bastava uma, e o not inverte uma condição."
       },
       {
        "t": "esc",
        "q": "Escreve a instrução que sai imediatamente de um ciclo.",
        "ok": [
         "break"
        ],
        "tok": [
         "break",
         "continue",
         "exit",
         "pass"
        ],
        "e": "break termina o ciclo e o programa continua depois dele. continue só salta para a iteração seguinte e pass não faz nada."
       }
      ]
     }
    ]
   },
   {
    "id": "funcoes",
    "nome": "Funções",
    "ficheiro": "funcoes.py",
    "ficha": {
     "oque": "Uma função é um bloco de código com nome que podes chamar sempre que precisares, em vez de repetir o mesmo código. Recebe valores (parâmetros), faz o seu trabalho e pode devolver um resultado com return. Em Python define-se com def.",
     "pontos": [
      [
       "def nome(parâmetros):",
       "Define a função; o corpo fica indentado e só corre quando a chamas com nome(argumentos)."
      ],
      [
       "return",
       "Devolve um valor a quem chamou e termina a função. Sem return, a função devolve None."
      ],
      [
       "print vs return",
       "print só mostra o valor no ecrã; return entrega-o ao programa para o poderes guardar ou usar em contas."
      ],
      [
       "Valor por omissão",
       "Em def f(x, n=2), se não passares n na chamada, ele vale 2."
      ],
      [
       "Âmbito (scope)",
       "Variáveis criadas dentro da função são locais: não existem fora dela. Uma variável global pode ser lida lá dentro."
      ],
      [
       "Reutilização",
       "Escreves a lógica uma vez e chamas a função com argumentos diferentes, o que reduz erros e facilita alterações."
      ]
     ],
     "exemplo": "def saudar(nome, saudacao=\"Olá\"):\n    return f\"{saudacao}, {nome}!\"\n\nprint(saudar(\"Ana\"))\nprint(saudar(\"Rui\", \"Boa tarde\"))\n# Olá, Ana!\n# Boa tarde, Rui!",
     "dica": "Se esqueceres o return, a função devolve None e o erro só aparece mais tarde, quando usares esse valor.",
     "fonte": "Documentação oficial do Python (docs.python.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "O que é um parâmetro de uma função?",
        "o": [
         "Um nome, na definição da função, que recebe o valor passado na chamada",
         "O resultado que a função devolve",
         "O comando que termina a função",
         "O ficheiro onde a função está guardada"
        ],
        "c": 0,
        "e": "Em def somar(a, b), a e b são parâmetros: ficam com os valores que passares ao chamar somar(2, 3). Esses valores concretos chamam-se argumentos."
       },
       {
        "t": "mc",
        "q": "Que palavra-chave define uma função em Python?",
        "o": [
         "function",
         "func",
         "def",
         "lambda"
        ],
        "c": 2,
        "e": "As funções definem-se com def, seguido do nome, dos parâmetros entre parênteses e de dois pontos. lambda existe, mas cria apenas pequenas funções anónimas de uma expressão."
       },
       {
        "t": "mc",
        "q": "O que devolve uma função que não tem return?",
        "o": [
         "0",
         "None",
         "Uma string vazia",
         "False"
        ],
        "c": 1,
        "e": "Sem return explícito, a função termina e devolve o valor especial None, que representa a ausência de valor."
       },
       {
        "t": "mc",
        "q": "Qual é a principal vantagem de usar funções?",
        "o": [
         "Fazem o programa correr sempre mais depressa",
         "Evitam a necessidade de usar variáveis",
         "Permitem escrever código sem indentação",
         "Reutilizas o mesmo código sem o repetir, e o programa fica mais organizado"
        ],
        "c": 3,
        "e": "Uma função escreve-se uma vez e usa-se muitas. Se a lógica mudar, corriges num único sítio. Não torna o programa mais rápido por si só."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que mostra este código?",
        "cod": "def dobro(n):\n    return n * 2\n\nprint(dobro(4))",
        "o": [
         "4",
         "8",
         "n * 2",
         "None"
        ],
        "c": 1,
        "e": "A chamada dobro(4) executa o corpo com n = 4, devolve 8, e é esse valor devolvido que o print mostra."
       },
       {
        "t": "cod",
        "q": "Qual é o resultado de executar este código?",
        "cod": "def somar(a, b):\n    print(a + b)\n\nr = somar(2, 3)\nprint(r)",
        "o": [
         "Só 5",
         "5 e depois 5",
         "None",
         "5 e depois None"
        ],
        "c": 3,
        "e": "Dentro de somar, o print mostra 5, mas a função não tem return e por isso devolve None. Logo, r vale None e o segundo print mostra None."
       },
       {
        "t": "cod",
        "q": "O que aparece no ecrã quando corres isto?",
        "cod": "def potencia(base, exp=2):\n    return base ** exp\n\nprint(potencia(3), potencia(2, 3))",
        "o": [
         "9 8",
         "6 6",
         "9 6",
         "3 8"
        ],
        "c": 0,
        "e": "Em potencia(3) o exp usa o valor por omissão 2: 3 ** 2 = 9. Em potencia(2, 3) passas exp = 3: 2 ** 3 = 8."
       },
       {
        "t": "cod",
        "q": "Qual é a saída deste código?",
        "cod": "x = 10\n\ndef f():\n    x = 5\n    return x\n\nprint(f(), x)",
        "o": [
         "5 5",
         "10 10",
         "5 10",
         "10 5"
        ],
        "c": 2,
        "e": "O x = 5 dentro da função cria uma variável local, que não altera o x global. Por isso f() devolve 5 e o x de fora continua a valer 10."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para a função devolver o resultado, de modo a que print(quadrado(3)) mostre 9.",
        "cod": "def quadrado(n):\n    ___ n * n\n\nprint(quadrado(3))",
        "o": [
         "print",
         "return",
         "import",
         "pass"
        ],
        "c": 1,
        "e": "return entrega o valor a quem chamou. Com print, a função mostraria 9 por si própria, mas devolveria None e o print de fora ainda escreveria None."
       },
       {
        "t": "cod",
        "q": "Onde está o erro neste código?",
        "cod": "def media(a, b):\n    return (a + b) / 2\n\nprint(media(4))",
        "o": [
         "O return não pode conter contas",
         "O operador / não funciona com inteiros",
         "A função devia chamar-se média",
         "Falta passar o segundo argumento (b) na chamada: dá TypeError"
        ],
        "c": 3,
        "e": "A função tem dois parâmetros obrigatórios, mas só foi passado um. O Python avisa que falta o argumento b. A correcção é media(4, 6), por exemplo."
       },
       {
        "t": "lac",
        "q": "Completa a definição para n ter o valor por omissão 10.",
        "cod": "def repetir(texto, n___10):\n    return texto * n",
        "o": [
         "=",
         "==",
         ":",
         "->"
        ],
        "c": 0,
        "e": "Os valores por omissão escrevem-se com = logo na definição (n=10). Se n não for passado na chamada, usa 10."
       },
       {
        "t": "cod",
        "q": "Porque é que este código dá erro?",
        "cod": "def contar():\n    total = 3\n\ncontar()\nprint(total)",
        "o": [
         "total é uma palavra reservada do Python",
         "contar() devia receber argumentos",
         "total é local à função e não existe fora dela (NameError)",
         "Falta um return no print"
        ],
        "c": 2,
        "e": "As variáveis criadas dentro de uma função são locais e desaparecem quando ela termina. Fora da função, o nome total não existe e dá NameError. Para usares o valor, devolve-o com return."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha de definição da função triplo, que recebe um parâmetro n.",
        "ok": [
         "def triplo(n):"
        ],
        "tok": [
         "def",
         "triplo(n):",
         "function",
         "triplo(n)",
         "return"
        ],
        "e": "Uma definição começa com def, o nome, os parâmetros entre parênteses e dois pontos no fim."
       },
       {
        "t": "esc",
        "q": "Escreve a linha (dentro da função) que devolve o resultado de n multiplicado por 3.",
        "ok": [
         "return n * 3",
         "return 3 * n"
        ],
        "tok": [
         "return",
         "n",
         "*",
         "3",
         "print",
         "+",
         "2"
        ],
        "e": "return devolve o valor calculado a quem chamou a função. Com print só o mostrarias no ecrã."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que chama a função triplo com o valor 5 e guarda o resultado na variável r.",
        "ok": [
         "r = triplo(5)",
         "r=triplo(5)"
        ],
        "tok": [
         "r",
         "=",
         "triplo(5)",
         "triplo(r)",
         "==",
         "print(triplo(5))"
        ],
        "e": "A chamada triplo(5) devolve um valor, e a atribuição guarda-o em r para o poderes usar depois."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de definição da função potencia, com os parâmetros base e exp, em que exp tem o valor por omissão 2.",
        "ok": [
         "def potencia(base, exp=2):",
         "def potencia(base, exp = 2):"
        ],
        "tok": [
         "def",
         "potencia(base,",
         "exp=2):",
         "potencia(exp,",
         "base=2):",
         "function"
        ],
        "e": "Os parâmetros com valor por omissão ficam depois dos obrigatórios. Escrever def potencia(exp=2, base) seria um erro de sintaxe."
       }
      ]
     }
    ]
   },
   {
    "id": "classes",
    "nome": "Classes, objetos e herança",
    "ficheiro": "classes.py",
    "ficha": {
     "oque": "Uma classe é um molde que define que dados (atributos) e que comportamentos (métodos) tem um tipo de objecto. Cada objecto, ou instância, é criado a partir da classe e tem os seus próprios valores. Com herança, uma classe filha reutiliza e especializa o que a classe mãe já tem.",
     "pontos": [
      [
       "class Nome:",
       "Define a classe; por convenção, o nome começa com maiúscula."
      ],
      [
       "__init__(self, ...)",
       "Método chamado automaticamente ao criar o objecto; serve para definir os atributos iniciais."
      ],
      [
       "self",
       "O próprio objecto: é o primeiro parâmetro de cada método e dá acesso a self.atributo."
      ],
      [
       "Instanciar",
       "rex = Cao('Rex') cria um objecto da classe Cao e chama o seu __init__."
      ],
      [
       "Herança",
       "class Cao(Animal): a classe Cao herda atributos e métodos de Animal."
      ],
      [
       "super() e sobrescrever",
       "super().__init__(...) chama o código da classe mãe; um método com o mesmo nome na filha sobrescreve o da mãe."
      ]
     ],
     "exemplo": "class Animal:\n    def __init__(self, nome):\n        self.nome = nome\n\n    def falar(self):\n        return f\"{self.nome} faz um som\"\n\nclass Cao(Animal):\n    def falar(self):\n        return f\"{self.nome} diz au au\"\n\nprint(Animal(\"Bicho\").falar())\nprint(Cao(\"Rex\").falar())\n# Bicho faz um som\n# Rex diz au au",
     "dica": "Esquecer o self na definição de um método (def falar():) dá TypeError ao chamá-lo; e dentro da classe escreve self.nome, não apenas nome.",
     "fonte": "Documentação oficial do Python (docs.python.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "O que é uma classe?",
        "o": [
         "Um molde que define os atributos e métodos dos objectos",
         "Um objecto concreto, com valores já preenchidos",
         "Uma função que devolve vários valores",
         "Uma lista de variáveis globais"
        ],
        "c": 0,
        "e": "A classe descreve como são e o que fazem os objectos de um tipo (por exemplo, Cao). Os objectos concretos, como rex, são criados a partir dela."
       },
       {
        "t": "mc",
        "q": "Qual é o papel do método __init__?",
        "o": [
         "Apagar o objecto da memória",
         "Inicializar o objecto quando ele é criado, definindo os seus atributos",
         "Mostrar o objecto no ecrã",
         "Criar a classe mãe"
        ],
        "c": 1,
        "e": "O Python chama __init__ automaticamente ao instanciar (Cao('Rex')). É aí que se guardam os valores iniciais em self.atributo."
       },
       {
        "t": "mc",
        "q": "Para que serve a herança?",
        "o": [
         "Copiar ficheiros entre projectos",
         "Esconder atributos de outras classes",
         "Impedir a criação de objectos",
         "Reutilizar o código de uma classe noutra e especializá-la"
        ],
        "c": 3,
        "e": "A classe filha recebe tudo o que a mãe tem e só precisa de acrescentar ou alterar o que é diferente. Evita repetir código comum, como o que Cao e Gato partilham de Animal."
       },
       {
        "t": "mc",
        "q": "O que representa self dentro de um método?",
        "o": [
         "A classe mãe",
         "O módulo actual",
         "O próprio objecto sobre o qual o método foi chamado",
         "O valor que o método devolve"
        ],
        "c": 2,
        "e": "Em rex.ladrar(), o Python passa rex como primeiro argumento, e é isso que self recebe. Por isso self.nome é o nome deste objecto e não o de outro."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que mostra este código?",
        "cod": "class Cao:\n    def __init__(self, nome):\n        self.nome = nome\n\nrex = Cao(\"Rex\")\nprint(rex.nome)",
        "o": [
         "nome",
         "Cao",
         "Rex",
         "Dá erro"
        ],
        "c": 2,
        "e": "Cao(\"Rex\") cria o objecto e chama __init__ com nome = \"Rex\", que fica guardado em self.nome. rex.nome devolve esse atributo."
       },
       {
        "t": "cod",
        "q": "Qual é o resultado de executar este código?",
        "cod": "class Conta:\n    def __init__(self, saldo):\n        self.saldo = saldo\n    def depositar(self, v):\n        self.saldo += v\nc = Conta(10)\nc.depositar(5)\nprint(c.saldo)",
        "o": [
         "15",
         "10",
         "5",
         "Dá erro"
        ],
        "c": 0,
        "e": "O objecto começa com saldo 10. O método depositar altera o atributo do próprio objecto (self.saldo += 5), por isso fica 15."
       },
       {
        "t": "cod",
        "q": "O que aparece no ecrã quando corres isto?",
        "cod": "class Animal:\n    def falar(self):\n        return \"...\"\nclass Gato(Animal):\n    pass\nprint(Gato().falar())",
        "o": [
         "Dá erro, porque Gato não define falar",
         "pass",
         "None",
         "..."
        ],
        "c": 3,
        "e": "Gato herda de Animal e por isso tem o método falar, mesmo sem o escrever. O pass é só um corpo vazio, porque a classe não acrescenta nada."
       },
       {
        "t": "cod",
        "q": "Qual é a saída deste código?",
        "cod": "class Animal:\n    def som(self):\n        return \"...\"\nclass Cao(Animal):\n    def som(self):\n        return \"au\" + super().som()\nprint(Cao().som())",
        "o": [
         "...",
         "au...",
         "au",
         "Dá erro"
        ],
        "c": 1,
        "e": "Cao sobrescreve som(), mas super().som() chama a versão da classe mãe, que devolve \"...\". O resultado junta os dois: \"au...\"."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para a classe Cao herdar de Animal.",
        "cod": "class Animal:\n    pass\n\nclass Cao(___):\n    pass",
        "o": [
         "Animal",
         "self",
         "object()",
         "super"
        ],
        "c": 0,
        "e": "A classe mãe escreve-se entre parênteses a seguir ao nome da filha: class Cao(Animal). self só existe dentro de métodos."
       },
       {
        "t": "cod",
        "q": "Onde está o erro neste código?",
        "cod": "class Cao:\n    def __init__(self, nome):\n        nome = nome\n\nd = Cao(\"Rex\")\nprint(d.nome)",
        "o": [
         "Falta um return no __init__",
         "O __init__ não pode receber o parâmetro nome",
         "Falta o self.: devia ser self.nome = nome, senão o atributo não fica guardado no objecto",
         "A classe devia chamar-se cao, em minúsculas"
        ],
        "c": 2,
        "e": "nome = nome só mexe numa variável local do __init__ e perde-se no fim. Sem self.nome, o objecto não tem atributo nome e d.nome dá AttributeError."
       },
       {
        "t": "lac",
        "q": "Completa o código para chamar o __init__ da classe mãe, que recebe nome.",
        "cod": "class Cao(Animal):\n    def __init__(self, nome):\n        ___.__init__(nome)\n        self.tipo = \"cão\"",
        "o": [
         "self",
         "Animal()",
         "super",
         "super()"
        ],
        "c": 3,
        "e": "super() dá acesso à classe mãe, e super().__init__(nome) executa o __init__ dela. Com self.__init__(nome) o método chamar-se-ia a si próprio sem parar."
       },
       {
        "t": "cod",
        "q": "Qual é a correcção deste código, que dá TypeError?",
        "cod": "class Cao:\n    def ladrar():\n        return \"au\"\n\nprint(Cao().ladrar())",
        "o": [
         "Trocar return por print",
         "Acrescentar self como parâmetro: def ladrar(self):",
         "Escrever Cao.ladrar sem parênteses",
         "Remover a classe e usar só uma função"
        ],
        "c": 1,
        "e": "Ao chamar Cao().ladrar(), o Python passa o objecto automaticamente como primeiro argumento. Sem o parâmetro self, o método recebe um argumento que não esperava."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha que define a classe Aluno, que herda da classe Pessoa.",
        "ok": [
         "class Aluno(Pessoa):"
        ],
        "tok": [
         "class",
         "Aluno(Pessoa):",
         "Pessoa(Aluno):",
         "Aluno:",
         "def"
        ],
        "e": "A classe mãe vai entre parênteses a seguir ao nome da filha. Pessoa(Aluno) inverteria a relação."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que define o método construtor, que recebe apenas self e nome.",
        "ok": [
         "def __init__(self, nome):"
        ],
        "tok": [
         "def",
         "__init__(self,",
         "nome):",
         "init(self,",
         "self):",
         "class"
        ],
        "e": "O construtor chama-se __init__, com dois underscores de cada lado, e o primeiro parâmetro é sempre self."
       },
       {
        "t": "esc",
        "q": "Escreve a linha (dentro do __init__ da classe filha) que chama o __init__ da classe mãe, passando nome.",
        "ok": [
         "super().__init__(nome)"
        ],
        "tok": [
         "super().__init__(nome)",
         "super.__init__(nome)",
         "self.__init__(nome)",
         "Pessoa(nome)"
        ],
        "e": "super() representa a classe mãe e .__init__(nome) executa o construtor dela. Sem os parênteses de super() não funciona."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que cria um objecto da classe Aluno, com o texto 'Ana' como nome, e o guarda na variável a.",
        "ok": [
         "a = Aluno('Ana')",
         "a=Aluno('Ana')"
        ],
        "tok": [
         "a",
         "=",
         "Aluno('Ana')",
         "Aluno.Ana",
         "==",
         "Aluno(a)"
        ],
        "e": "Instanciar é chamar a classe como se fosse uma função. O Python cria o objecto e passa 'Ana' ao __init__."
       }
      ]
     }
    ]
   }
  ]
 },
 {
  "id": "js",
  "nome": "JavaScript",
  "cor": "#f7df1e",
  "descricao": "Linguagem da web: DOM, dados e pedidos",
  "topicos": [
   {
    "id": "dom",
    "nome": "DOM e eventos",
    "ficheiro": "dom.js",
    "ficha": {
     "oque": "O DOM (Document Object Model) é a representação da página HTML como uma árvore de objetos que o JavaScript pode ler e alterar. Com ele escolhes elementos, mudas o texto e as classes, crias elementos novos e reages a ações do utilizador (cliques, teclas, envio de formulários) através de eventos.",
     "pontos": [
      [
       "document.getElementById('id')",
       "Devolve o elemento com esse id (sem #), ou null se não existir."
      ],
      [
       "document.querySelector('seletor')",
       "Devolve o primeiro elemento que corresponde ao seletor CSS (ou null); querySelectorAll devolve todos."
      ],
      [
       "textContent vs innerHTML",
       "textContent trata o valor como texto simples; innerHTML interpreta-o como HTML."
      ],
      [
       "element.classList",
       "Tem os métodos add, remove, toggle e contains para gerir as classes de um elemento."
      ],
      [
       "addEventListener('evento', função)",
       "Regista uma função que corre sempre que o evento acontece (por exemplo 'click' ou 'submit')."
      ],
      [
       "event.preventDefault()",
       "Cancela o comportamento por omissão do evento, como o envio de um formulário. Para criar elementos usa document.createElement() e append()."
      ]
     ],
     "exemplo": "const botao = document.querySelector('#guardar');\nbotao.addEventListener('click', () => {\n  const item = document.createElement('li');\n  item.textContent = 'Nova tarefa';\n  document.querySelector('ul').append(item);\n});",
     "dica": "Com innerHTML, texto escrito por um utilizador pode introduzir código na página; se só queres mostrar texto, usa textContent.",
     "fonte": "MDN Web Docs (developer.mozilla.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "O que é o DOM?",
        "o": [
         "Uma linguagem para dar estilo às páginas web",
         "Uma representação da página em forma de árvore de objetos que o JavaScript pode ler e alterar",
         "Um programa que compila JavaScript para o navegador",
         "Uma base de dados guardada no navegador"
        ],
        "c": 1,
        "e": "O navegador transforma o HTML numa árvore de objetos (o DOM). É através dessa árvore que o JavaScript lê e altera a página sem a recarregar."
       },
       {
        "t": "mc",
        "q": "O que devolve document.getElementById('titulo') quando não existe nenhum elemento com esse id?",
        "o": [
         "undefined",
         "Uma lista vazia",
         "null",
         "Um erro que pára o programa"
        ],
        "c": 2,
        "e": "getElementById devolve null quando não encontra nada. Por isso, se tentares usar o resultado (por exemplo, el.textContent = ...), obténs um erro só nessa linha."
       },
       {
        "t": "mc",
        "q": "Qual é a diferença principal ao escrever num elemento com innerHTML ou com textContent?",
        "o": [
         "innerHTML interpreta o valor como HTML; textContent trata-o como texto simples",
         "textContent interpreta o valor como HTML; innerHTML trata-o como texto simples",
         "Não há diferença, são sinónimos",
         "textContent só funciona em parágrafos"
        ],
        "c": 0,
        "e": "Com innerHTML, '<b>olá</b>' cria mesmo um elemento b. Com textContent aparece o texto literal, o que é mais seguro quando o conteúdo vem do utilizador."
       },
       {
        "t": "mc",
        "q": "Para que serve o método addEventListener?",
        "o": [
         "Cria um novo elemento HTML",
         "Pede dados a um servidor",
         "Guarda dados no navegador",
         "Regista uma função para correr quando um evento (como um clique) acontece"
        ],
        "c": 3,
        "e": "Dizes qual é o evento ('click', 'submit', 'keydown'...) e que função deve correr. O JavaScript chama essa função cada vez que o evento acontece."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que aparece no ecrã dentro do h1?",
        "cod": "const titulo = document.querySelector('h1');\ntitulo.textContent = 'Olá, <b>mundo</b>';",
        "o": [
         "Olá, mundo, com a palavra mundo a negrito",
         "O texto Olá, <b>mundo</b> tal como está, com as etiquetas visíveis",
         "Apenas Olá,",
         "Dá erro porque o texto contém etiquetas"
        ],
        "c": 1,
        "e": "textContent não interpreta HTML: as etiquetas são tratadas como caracteres normais e ficam visíveis. Com innerHTML, mundo apareceria a negrito."
       },
       {
        "t": "cod",
        "q": "Assumindo que a caixa não tinha a classe ativa no início, ela tem-na no fim?",
        "cod": "const caixa = document.getElementById('caixa');\ncaixa.classList.add('ativa');\ncaixa.classList.toggle('ativa');",
        "o": [
         "Sim, o add tem prioridade sobre o toggle",
         "Sim, mas aparece duas vezes na lista de classes",
         "Dá erro porque add e toggle não se podem usar juntos",
         "Não, o toggle remove a classe que o add acabou de adicionar"
        ],
        "c": 3,
        "e": "toggle inverte: se a classe existe, remove-a; se não existe, adiciona-a. Como o add acabou de a pôr, o toggle tira-a. (A classe nunca aparece repetida na lista.)"
       },
       {
        "t": "cod",
        "q": "Numa página com 3 elementos com a classe item, o que faz este código?",
        "cod": "const primeiro = document.querySelector('.item');\nprimeiro.textContent = 'A';",
        "o": [
         "Só o primeiro elemento com a classe item fica com o texto A",
         "Os 3 elementos ficam com o texto A",
         "Só o último elemento com a classe item fica com o texto A",
         "Dá erro porque há mais do que um elemento"
        ],
        "c": 0,
        "e": "querySelector devolve apenas o primeiro elemento que corresponde ao seletor. Para obteres todos usa querySelectorAll, que devolve uma lista que se percorre com forEach ou um ciclo."
       },
       {
        "t": "cod",
        "q": "O que acontece quando o utilizador carrega no botão de submeter do formulário?",
        "cod": "const form = document.querySelector('form');\nform.addEventListener('submit', (event) => {\n  event.preventDefault();\n  console.log('enviado');\n});",
        "o": [
         "A página recarrega e a consola não chega a mostrar nada",
         "A consola mostra enviado e a página recarrega na mesma",
         "A consola mostra enviado e o formulário não é enviado (a página não recarrega)",
         "Nada, porque o evento submit não existe em formulários"
        ],
        "c": 2,
        "e": "Por omissão, submeter um formulário envia-o e recarrega a página. preventDefault() cancela esse comportamento, mas o resto da função continua a correr, por isso vês 'enviado'."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para que a mensagem apareça sempre que se clica no botão.",
        "cod": "const btn = document.querySelector('#menu');\nbtn.___('click', () => console.log('clicou'));",
        "o": [
         "addEvent",
         "onClick",
         "addEventListener",
         "listen"
        ],
        "c": 2,
        "e": "O método que regista uma função para um evento é addEventListener(tipo, função). Os outros nomes não existem em elementos DOM com este formato."
       },
       {
        "t": "cod",
        "q": "Este código dá erro, porque el é null. Qual é a correção?",
        "cod": "const el = document.getElementById('#saudacao');\nel.textContent = 'Olá';",
        "o": [
         "Escrever getElementById('saudacao'), sem o #",
         "Trocar textContent por text",
         "Trocar as aspas simples por aspas duplas",
         "Usar querySelectorAll em vez de getElementById"
        ],
        "c": 0,
        "e": "getElementById recebe só o nome do id, sem #. O # é sintaxe de seletores CSS e usa-se em querySelector('#saudacao'). Com '#saudacao' não há elemento, o resultado é null e a atribuição falha."
       },
       {
        "t": "lac",
        "q": "Completa o código para remover a classe escondido do elemento.",
        "cod": "const aviso = document.querySelector('#aviso');\naviso.classList.___('escondido');",
        "o": [
         "delete",
         "clear",
         "drop",
         "remove"
        ],
        "c": 3,
        "e": "classList tem add, remove, toggle e contains. Atenção: toggle não serve aqui, porque voltaria a adicionar a classe se ela não existisse."
       },
       {
        "t": "cod",
        "q": "O item Pão não aparece na página. O que falta?",
        "cod": "const lista = document.querySelector('ul');\nconst li = document.createElement('li');\nli.textContent = 'Pão';",
        "o": [
         "Trocar createElement por createNode",
         "Acrescentar lista.append(li) para inserir o li na lista",
         "Trocar textContent por text",
         "Usar lista.innerHTML(li)"
        ],
        "c": 1,
        "e": "createElement só cria o elemento na memória. Ele só aparece na página depois de o inserires na árvore do DOM, por exemplo com append() ou appendChild()."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha que guarda na constante botao o primeiro elemento com a classe enviar (usa querySelector).",
        "ok": [
         "const botao = document.querySelector('.enviar')"
        ],
        "tok": [
         "const",
         "botao",
         "=",
         "document.querySelector('.enviar')",
         "let",
         "document.querySelectorAll('.enviar')",
         "document.querySelector('enviar')"
        ],
        "e": "O seletor de classe leva um ponto: '.enviar'. querySelector devolve o primeiro elemento; querySelectorAll devolveria uma lista."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que acrescenta a classe destaque ao elemento caixa (variável já existente), usando classList.",
        "ok": [
         "caixa.classList.add('destaque')"
        ],
        "tok": [
         "caixa.classList.add('destaque')",
         "caixa.classList.remove('destaque')",
         "caixa.className.add('destaque')",
         "caixa.classList.add('caixa')"
        ],
        "e": "classList.add recebe o nome da classe sem ponto. remove faria o contrário e className é uma string, por isso não tem o método add."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que cria um elemento p e o guarda na constante par.",
        "ok": [
         "const par = document.createElement('p')"
        ],
        "tok": [
         "const",
         "par",
         "=",
         "document.createElement('p')",
         "let",
         "document.createElement('par')",
         "document.createTag('p')"
        ],
        "e": "createElement recebe o nome da etiqueta HTML ('p'), não o nome da variável. O elemento criado ainda não está na página até o inseres com append()."
       },
       {
        "t": "esc",
        "q": "Dentro de um manipulador de evento com o parâmetro event, escreve a linha que cancela o comportamento por omissão.",
        "ok": [
         "event.preventDefault()"
        ],
        "tok": [
         "event.preventDefault()",
         "event.stopDefault()",
         "event.preventDefault",
         "event.cancel()"
        ],
        "e": "preventDefault() é um método, por isso precisa dos parênteses. Sem eles apenas referes a função e não a chamas, e o comportamento por omissão continua."
       }
      ]
     }
    ]
   },
   {
    "id": "arrays",
    "nome": "Arrays e objetos",
    "ficheiro": "arrays-objetos.js",
    "ficha": {
     "oque": "Os arrays guardam listas ordenadas de valores e os objetos guardam pares chave-valor (propriedades). São a base de quase todos os dados em JavaScript, e o JSON é o formato de texto usado para os trocar entre programas e servidores.",
     "pontos": [
      [
       "push, pop e length",
       "push acrescenta ao fim do array; pop remove e devolve o último elemento; length é o número de elementos."
      ],
      [
       "map, filter, find, forEach",
       "map devolve um novo array com o resultado da função para cada elemento; filter devolve só os que passam no teste; find devolve o primeiro que passa (ou undefined); forEach só executa a função e não devolve nada."
      ],
      [
       "pessoa.nome e pessoa['nome']",
       "Duas formas de aceder a uma propriedade; os parênteses retos servem quando a chave está guardada numa variável."
      ],
      [
       "const { nome, idade } = pessoa",
       "Desestruturação: tira propriedades de um objeto para variáveis com o mesmo nome."
      ],
      [
       "JSON.stringify / JSON.parse",
       "stringify converte um valor JavaScript em texto JSON; parse converte texto JSON de volta num valor."
      ],
      [
       "=== vs ==",
       "=== compara valor e tipo, sem conversões; == converte os tipos antes de comparar, o que dá resultados surpreendentes."
      ]
     ],
     "exemplo": "const pessoas = [\n  { nome: 'Ana', idade: 20 },\n  { nome: 'Rui', idade: 17 }\n];\nconst adultos = pessoas.filter(p => p.idade >= 18);\nconst { nome } = adultos[0];\nconsole.log(nome); // Ana\nconsole.log(JSON.stringify(adultos[0])); // {\"nome\":\"Ana\",\"idade\":20}",
     "dica": "== converte tipos antes de comparar (0 == '0' é true), por isso usa quase sempre ===.",
     "fonte": "MDN Web Docs (developer.mozilla.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Qual destas estruturas guarda uma lista ordenada de valores acedidos por posição (0, 1, 2...)?",
        "o": [
         "Um objeto",
         "Um array",
         "Uma função",
         "Um valor booleano"
        ],
        "c": 1,
        "e": "Os arrays guardam valores por ordem e acedem-se por índice, começando em 0. Os objetos guardam pares chave-valor, acedidos pelo nome da chave."
       },
       {
        "t": "mc",
        "q": "O que faz o método push de um array?",
        "o": [
         "Remove o último elemento e devolve-o",
         "Remove o primeiro elemento",
         "Ordena o array",
         "Acrescenta um ou mais elementos ao fim do array"
        ],
        "c": 3,
        "e": "push altera o array original, acrescentando elementos no fim. O método que remove o último é o pop."
       },
       {
        "t": "mc",
        "q": "Para que serve JSON.stringify?",
        "o": [
         "Converte um valor JavaScript numa string em formato JSON",
         "Converte texto JSON num objeto JavaScript",
         "Apaga um objeto da memória",
         "Ordena as propriedades de um objeto"
        ],
        "c": 0,
        "e": "stringify transforma um objeto ou array em texto JSON, útil para guardar ou enviar dados. O contrário, de texto para objeto, faz-se com JSON.parse."
       },
       {
        "t": "mc",
        "q": "Qual é a diferença entre === e == ?",
        "o": [
         "Não há diferença, são dois nomes para o mesmo operador",
         "== atribui um valor e === compara",
         "=== compara sem converter tipos (valor e tipo têm de ser iguais); == tenta converter os tipos antes de comparar",
         "=== só funciona com números"
        ],
        "c": 2,
        "e": "Com ===, 5 === '5' é false porque os tipos diferem. Com ==, 5 == '5' é true porque a string é convertida. A atribuição faz-se com um único =."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que mostra a consola neste código com map?",
        "cod": "const numeros = [1, 2, 3];\nconst dobros = numeros.map(n => n * 2);\nconsole.log(dobros);",
        "o": [
         "[1, 2, 3]",
         "6",
         "[2, 4, 6]",
         "undefined"
        ],
        "c": 2,
        "e": "map aplica a função a cada elemento e devolve um novo array com os resultados. O array numeros não é alterado."
       },
       {
        "t": "cod",
        "q": "Quantos elementos tem maiores e, portanto, que número mostra a consola?",
        "cod": "const idades = [12, 18, 25, 9];\nconst maiores = idades.filter(i => i >= 18);\nconsole.log(maiores.length);",
        "o": [
         "2",
         "4",
         "18",
         "[18, 25]"
        ],
        "c": 0,
        "e": "filter fica só com os elementos para os quais a função devolve true (18 e 25), e length conta-os. O que se imprime é o número 2, não o array."
       },
       {
        "t": "cod",
        "q": "Que valores mostra a consola no fim deste código?",
        "cod": "const lista = ['a', 'b'];\nlista.push('c');\nconst ultimo = lista.pop();\nconsole.log(ultimo, lista.length);",
        "o": [
         "c 3",
         "a 2",
         "b 2",
         "c 2"
        ],
        "c": 3,
        "e": "push junta 'c' ao fim e pop remove-o e devolve-o, por isso ultimo é 'c'. Depois do pop, a lista volta a ter 2 elementos."
       },
       {
        "t": "cod",
        "q": "Que valores mostra a consola, separados por espaço?",
        "cod": "const carro = { marca: 'Fiat', ano: 2010 };\nconst chave = 'ano';\nconsole.log(carro[chave], carro.chave);",
        "o": [
         "2010 2010",
         "2010 undefined",
         "undefined undefined",
         "ano ano"
        ],
        "c": 1,
        "e": "Com parênteses retos, o JavaScript usa o valor da variável chave ('ano'). Com carro.chave procura uma propriedade chamada literalmente chave, que não existe, e devolve undefined."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa para guardar em maca o primeiro elemento que cumpre a condição (a própria string, não um array).",
        "cod": "const frutas = ['maçã', 'pera', 'uva'];\nconst maca = frutas.___(f => f === 'maçã');\nconsole.log(maca); // maçã",
        "o": [
         "map",
         "filter",
         "find",
         "forEach"
        ],
        "c": 2,
        "e": "find devolve o primeiro elemento que passa no teste. filter devolveria um array ['maçã'], map devolveria um array de booleanos e forEach devolve undefined."
       },
       {
        "t": "cod",
        "q": "O objetivo é converter o objeto pessoa numa string JSON, mas este código dá erro. Qual é a correção?",
        "cod": "const pessoa = { nome: 'Ana', idade: 20 };\nconst texto = JSON.parse(pessoa);",
        "o": [
         "Trocar JSON.parse por JSON.stringify",
         "Trocar const por let",
         "Escrever pessoa entre aspas",
         "Trocar JSON.parse por JSON.object"
        ],
        "c": 0,
        "e": "JSON.parse espera uma string em JSON e converte-a em objeto. Para o caminho inverso, de objeto para texto, usa-se JSON.stringify."
       },
       {
        "t": "lac",
        "q": "Completa a desestruturação para obter a nota do objeto aluno.",
        "cod": "const aluno = { nome: 'Rui', nota: 15 };\nconst { ___ } = aluno;\nconsole.log(nota); // 15",
        "o": [
         "aluno.nota",
         "[nota]",
         "nome",
         "nota"
        ],
        "c": 3,
        "e": "Na desestruturação de objetos, o nome entre chavetas tem de coincidir com o da propriedade, e cria uma variável com esse nome. Com [nota] seria desestruturação de array, que não funciona num objeto."
       },
       {
        "t": "cod",
        "q": "O programador esperava [2, 4, 6], mas vê undefined. Qual é a correção?",
        "cod": "const nums = [1, 2, 3];\nconst dobros = nums.forEach(n => n * 2);\nconsole.log(dobros);",
        "o": [
         "Trocar const por let",
         "Trocar forEach por map",
         "Trocar n * 2 por n + n",
         "Acrescentar nums.length no fim"
        ],
        "c": 1,
        "e": "forEach só executa a função para cada elemento e devolve sempre undefined. Para obter um novo array com os resultados é preciso usar map."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha que cria a constante nome a partir do objeto pessoa, usando desestruturação.",
        "ok": [
         "const { nome } = pessoa",
         "const {nome} = pessoa"
        ],
        "tok": [
         "const",
         "{",
         "nome",
         "}",
         "=",
         "pessoa",
         "let",
         "[",
         "]"
        ],
        "e": "As chavetas indicam desestruturação de um objeto: é criada a variável nome com o valor de pessoa.nome. Com parênteses retos seria desestruturação de um array."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que converte o objeto aluno numa string JSON e a guarda na constante texto.",
        "ok": [
         "const texto = JSON.stringify(aluno)"
        ],
        "tok": [
         "const",
         "texto",
         "=",
         "JSON.stringify(aluno)",
         "let",
         "JSON.parse(aluno)",
         "JSON.stringify(texto)"
        ],
        "e": "stringify recebe o valor a converter (aluno) e devolve o texto, que é guardado em texto. O argumento é o objeto, não a variável de destino."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que guarda na constante total o número de elementos do array lista.",
        "ok": [
         "const total = lista.length"
        ],
        "tok": [
         "const",
         "total",
         "=",
         "lista.length",
         "let",
         "lista.size",
         "lista.length()"
        ],
        "e": "length é uma propriedade, não um método: não leva parênteses. Em arrays não existe size."
       },
       {
        "t": "esc",
        "q": "Escreve a condição que verifica, sem conversão de tipos, se a variável x é igual a 5 (apenas a condição).",
        "ok": [
         "x === 5"
        ],
        "tok": [
         "x",
         "===",
         "5",
         "==",
         "=",
         "!=="
        ],
        "e": "=== compara valor e tipo. Um único = é uma atribuição e == converteria os tipos antes de comparar."
       }
      ]
     }
    ]
   },
   {
    "id": "async",
    "nome": "Funções, promessas e fetch",
    "ficheiro": "fetch-async.js",
    "ficha": {
     "oque": "Em JavaScript, o código que demora (pedidos à rede, temporizadores) não bloqueia o resto do programa: o resultado chega mais tarde, através de callbacks ou de promessas (Promise). O fetch faz pedidos HTTP e devolve uma promessa; com async/await escreves esse código em sequência, quase como código normal.",
     "pontos": [
      [
       "function vs arrow",
       "function nome(a) { ... } declara uma função com nome; a arrow function (a) => ... é uma forma mais curta, normalmente guardada numa const."
      ],
      [
       "callback e setTimeout",
       "Um callback é uma função passada a outra para ser chamada mais tarde; setTimeout(função, ms) chama-a uma vez, passados ms milissegundos."
      ],
      [
       "Promise, then e catch",
       "Uma Promise representa um resultado futuro; then recebe o valor quando há sucesso e catch trata o erro."
      ],
      [
       "async e await",
       "Uma função async devolve sempre uma Promise; await espera que uma Promise termine e devolve o seu valor."
      ],
      [
       "fetch e response.json()",
       "fetch(url) devolve uma Promise com a resposta; response.json() lê o corpo como JSON e devolve outra Promise com os dados."
      ],
      [
       "response.ok",
       "fetch só rejeita em falhas de rede; um erro HTTP como 404 não rejeita, por isso verifica response.ok."
      ]
     ],
     "exemplo": "async function carregarUtilizador() {\n  try {\n    const resposta = await fetch('https://exemplo.pt/api/utilizador/1');\n    if (!resposta.ok) throw new Error('Erro ' + resposta.status);\n    const dados = await resposta.json();\n    console.log(dados.nome);\n  } catch (erro) {\n    console.error(erro.message);\n  }\n}\ncarregarUtilizador();",
     "dica": "Se te esqueceres do await, ficas com uma Promise em vez do valor e vês Promise { <pending> } na consola.",
     "fonte": "MDN Web Docs (developer.mozilla.org)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "O que é uma Promise?",
        "o": [
         "Uma variável cujo valor não pode ser alterado",
         "Um array que guarda valores por ordem",
         "Um objeto que representa o resultado futuro (sucesso ou erro) de uma operação assíncrona",
         "Uma função que só pode ser chamada uma vez"
        ],
        "c": 2,
        "e": "Uma Promise começa pendente e acaba cumprida (com um valor) ou rejeitada (com um erro). then e catch servem para reagir a cada um desses desfechos."
       },
       {
        "t": "mc",
        "q": "Para que serve response.json() num pedido fetch?",
        "o": [
         "Lê o corpo da resposta como JSON e devolve uma Promise com os dados",
         "Envia dados em JSON para o servidor",
         "Verifica se a resposta tem erros",
         "Converte um objeto numa string JSON"
        ],
        "c": 0,
        "e": "A resposta do fetch traz o corpo em bruto. response.json() lê-o e interpreta-o como JSON, e como essa leitura é assíncrona devolve uma Promise."
       },
       {
        "t": "mc",
        "q": "O que faz setTimeout(funcao, 2000)?",
        "o": [
         "Chama a função de 2 em 2 segundos, para sempre",
         "Pára o programa durante 2 segundos",
         "Repete a função 2000 vezes",
         "Chama a função uma única vez, passados 2000 milissegundos"
        ],
        "c": 3,
        "e": "setTimeout agenda uma única chamada e o programa continua a correr entretanto, não fica parado. Para repetir de tempos a tempos existe setInterval."
       },
       {
        "t": "mc",
        "q": "Para que serve a palavra await?",
        "o": [
         "Declara uma função assíncrona",
         "Espera que uma Promise termine e devolve o seu valor (usa-se dentro de funções async)",
         "Cria uma nova Promise",
         "Cancela um pedido fetch"
        ],
        "c": 1,
        "e": "await pausa a função async até a Promise estar concluída e dá-te o valor já pronto, sem precisares de then. A palavra que declara a função assíncrona é async."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que mostra a consola?",
        "cod": "const dobro = (n) => n * 2;\nconsole.log(dobro(5));",
        "o": [
         "undefined",
         "10",
         "25",
         "Dá erro porque falta return"
        ],
        "c": 1,
        "e": "Numa arrow function sem chavetas, o resultado da expressão é devolvido automaticamente. Só com chavetas é que precisarias de escrever return."
       },
       {
        "t": "cod",
        "q": "Por que ordem aparecem as letras na consola?",
        "cod": "console.log('A');\nsetTimeout(() => console.log('B'), 0);\nconsole.log('C');",
        "o": [
         "A B C",
         "B A C",
         "C B A",
         "A C B"
        ],
        "c": 3,
        "e": "Mesmo com 0 ms, a função do setTimeout só corre depois de o código síncrono atual acabar. Por isso A e C aparecem primeiro e B no fim."
       },
       {
        "t": "cod",
        "q": "O que faz este código quando o pedido tem sucesso e o servidor devolve uma lista de 3 tarefas em JSON?",
        "cod": "fetch('/api/tarefas')\n  .then(resposta => resposta.json())\n  .then(tarefas => console.log(tarefas.length))\n  .catch(erro => console.error('Falhou'));",
        "o": [
         "Mostra 3 na consola",
         "Mostra Falhou na consola",
         "Mostra a Response completa",
         "Mostra Promise { <pending> }"
        ],
        "c": 0,
        "e": "O primeiro then converte o corpo em JSON; o segundo recebe já o array de tarefas e imprime o seu length. O catch só corre se alguma etapa falhar."
       },
       {
        "t": "cod",
        "q": "O que contém a variável r?",
        "cod": "async function obter() {\n  return 42;\n}\nconst r = obter();\nconsole.log(r);",
        "o": [
         "O número 42",
         "undefined",
         "Uma Promise que fica cumprida com o valor 42",
         "A string '42'"
        ],
        "c": 2,
        "e": "Uma função async devolve sempre uma Promise, mesmo que dentro dela faças return de um valor simples. Para obteres o 42 tens de usar await ou then."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para esperar pela resposta do fetch.",
        "cod": "async function carregar() {\n  const resposta = ___ fetch('/api/dados');\n  const dados = await resposta.json();\n}",
        "o": [
         "then",
         "async",
         "return",
         "await"
        ],
        "c": 3,
        "e": "Sem await, resposta seria uma Promise em vez da resposta HTTP e resposta.json() falharia. await só pode ser usado dentro de funções async (ou no topo de módulos)."
       },
       {
        "t": "cod",
        "q": "dados.nome aparece como undefined. Qual é o erro?",
        "cod": "async function mostrar() {\n  const resposta = await fetch('/api/utilizador');\n  const dados = resposta.json();\n  console.log(dados.nome);\n}",
        "o": [
         "Falta await antes de resposta.json(), que devolve uma Promise",
         "Falta async antes de console.log",
         "fetch não pode ser usado com await",
         "dados devia ser declarado com let"
        ],
        "c": 0,
        "e": "response.json() também é assíncrono. Sem await, dados é uma Promise, que não tem propriedade nome, daí o undefined."
       },
       {
        "t": "lac",
        "q": "Completa o código para tratar um eventual erro do pedido.",
        "cod": "fetch('/api/produtos')\n  .then(r => r.json())\n  .___(erro => console.error(erro));",
        "o": [
         "finally",
         "catch",
         "else",
         "error"
        ],
        "c": 1,
        "e": "catch recebe o erro de qualquer etapa anterior da cadeia. finally também existe, mas corre sempre e não recebe o erro."
       },
       {
        "t": "cod",
        "q": "A mensagem aparece logo, sem esperar 1 segundo. Qual é a correção?",
        "cod": "function mostrar() {\n  console.log('Olá');\n}\nsetTimeout(mostrar(), 1000);",
        "o": [
         "Aumentar 1000 para 5000",
         "Trocar function por =>",
         "Escrever setTimeout(mostrar, 1000), sem parênteses, para passar a função em vez de a chamar",
         "Escrever await antes de setTimeout"
        ],
        "c": 2,
        "e": "mostrar() chama a função de imediato e passa o seu resultado (undefined) ao setTimeout. Sem parênteses, passas a própria função como callback para ser chamada mais tarde."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Dentro de uma função async, escreve a linha que guarda na constante resposta o resultado de fetch('/api/notas'), à espera da Promise.",
        "ok": [
         "const resposta = await fetch('/api/notas')"
        ],
        "tok": [
         "const",
         "resposta",
         "=",
         "await",
         "fetch('/api/notas')",
         "let",
         "fetch('/api/nota')",
         "async"
        ],
        "e": "await vem antes de fetch(...) para obteres a resposta e não uma Promise. A constante chama-se resposta e o endereço é exatamente o do enunciado."
       },
       {
        "t": "esc",
        "q": "Escreve a linha que guarda na constante dobro uma arrow function com um parâmetro n (sem parênteses) que devolve n * 2.",
        "ok": [
         "const dobro = n => n * 2",
         "const dobro = (n) => n * 2"
        ],
        "tok": [
         "const",
         "dobro",
         "=",
         "n",
         "=>",
         "n",
         "*",
         "2",
         "let",
         "function",
         "->"
        ],
        "e": "A arrow function usa => entre os parâmetros e o corpo. Com um só parâmetro os parênteses são opcionais e, sem chavetas, o resultado é devolvido automaticamente."
       },
       {
        "t": "esc",
        "q": "A função avisar já existe. Escreve a linha que a chama daqui a 2000 milissegundos, usando setTimeout.",
        "ok": [
         "setTimeout(avisar,2000)",
         "setTimeout(avisar, 2000)"
        ],
        "tok": [
         "setTimeout(avisar,2000)",
         "setTimeout(avisar(),2000)",
         "setTimeout(2000,avisar)",
         "setInterval(avisar,2000)"
        ],
        "e": "setTimeout recebe primeiro a função (sem parênteses, para não a chamar já) e depois o atraso em milissegundos. setInterval repetiria a chamada."
       },
       {
        "t": "esc",
        "q": "Escreve a primeira linha de uma função assíncrona chamada carregar, sem parâmetros, com function e a chaveta de abertura.",
        "ok": [
         "async function carregar() {",
         "async function carregar(){"
        ],
        "tok": [
         "async",
         "function",
         "carregar()",
         "{",
         "await",
         "=>",
         "}"
        ],
        "e": "A palavra async vem antes de function. É ela que faz a função devolver sempre uma Promise e permite usar await lá dentro."
       }
      ]
     }
    ]
   }
  ]
 },
 {
  "id": "git",
  "nome": "Git e GitHub",
  "cor": "#f05133",
  "descricao": "Versões, commits, ramos e colaboração",
  "topicos": [
   {
    "id": "desfazer",
    "nome": "Desfazer alterações",
    "ficheiro": "desfazer.md",
    "ficha": {
     "oque": "Desfazer alterações é voltar atrás no Git sem perderes o controlo do que aconteceu. Consoante o ponto em que estás (ficheiro editado, ficheiro já preparado com git add, ou commit já feito), usas um comando diferente. Uns são seguros e outros apagam trabalho para sempre.",
     "pontos": [
      [
       "git restore <ficheiro>",
       "Descarta as alterações ainda não preparadas do ficheiro. Perigoso: não há volta atrás."
      ],
      [
       "git restore --staged <ficheiro>",
       "Tira o ficheiro da área de preparação, mas mantém as alterações no ficheiro. Seguro."
      ],
      [
       "git revert <commit>",
       "Cria um novo commit que anula as alterações de outro. Não reescreve a história, por isso é seguro."
      ],
      [
       "git reset --soft / --mixed / --hard",
       "Move o ramo para outro commit. --soft mantém tudo, --mixed (por defeito) limpa a área de preparação, --hard apaga também as alterações nos ficheiros."
      ],
      [
       "git commit --amend",
       "Substitui o último commit por um novo, por exemplo para corrigir a mensagem ou acrescentar um ficheiro esquecido."
      ]
     ],
     "exemplo": "git restore style.css\ngit restore --staged index.html\ngit revert HEAD\ngit reset --soft HEAD~1\ngit commit --amend -m \"fix\"",
     "dica": "Não uses reset --hard nem --amend em commits já enviados e partilhados no GitHub; para esses usa git revert.",
     "fonte": "Documentação oficial do Git (git-scm.com/docs)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Para que serve o comando git restore ficheiro.txt?",
        "o": [
         "Apagar o ficheiro do disco e do histórico",
         "Criar um novo commit com o ficheiro",
         "Enviar o ficheiro para o GitHub",
         "Descartar as alterações ainda não preparadas em ficheiro.txt"
        ],
        "c": 3,
        "e": "Sem opções, git restore repõe o ficheiro no estado da área de preparação (ou do último commit). As alterações descartadas não ficam guardadas em lado nenhum, por isso usa-o com cuidado."
       },
       {
        "t": "mc",
        "q": "O que faz git revert num commit?",
        "o": [
         "Apaga o commit do histórico",
         "Junta dois ramos",
         "Cria um novo commit que anula as alterações desse commit",
         "Descarta as alterações dos ficheiros"
        ],
        "c": 2,
        "e": "O revert não mexe no commit original: acrescenta um commit novo com o efeito contrário. Como a história não é reescrita, é a opção segura para commits já partilhados."
       },
       {
        "t": "mc",
        "q": "Qual destes comandos é perigoso porque apaga, sem recuperação possível, alterações ainda não commitadas?",
        "o": [
         "git revert HEAD",
         "git reset --hard",
         "git restore --staged index.html",
         "git commit --amend"
        ],
        "c": 1,
        "e": "O reset --hard faz o ramo, a área de preparação e os ficheiros coincidirem com o commit indicado, perdendo o que não foi commitado. Os outros comandos não destroem alterações dos ficheiros."
       },
       {
        "t": "mc",
        "q": "O que significa corrigir o último commit com git commit --amend?",
        "o": [
         "Substituí-lo por um novo commit, com a mensagem ou o conteúdo corrigidos",
         "Anulá-lo com um commit adicional",
         "Apagar todos os commits anteriores",
         "Enviá-lo para o GitHub"
        ],
        "c": 0,
        "e": "O --amend não acrescenta um commit: troca o último por outro novo. Por isso muda o identificador do commit e convém não o fazer depois de o teres enviado e partilhado."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que acontece ao ficheiro index.html depois destes dois comandos?",
        "cod": "git add index.html\ngit restore --staged index.html",
        "o": [
         "Fica fora da área de preparação, mas mantém as alterações feitas",
         "As alterações ao ficheiro são apagadas",
         "É criado um commit com o ficheiro",
         "O ficheiro é apagado do disco"
        ],
        "c": 0,
        "e": "O primeiro comando prepara o ficheiro e o segundo desfaz só essa preparação. O conteúdo do ficheiro não é tocado, porque --staged actua apenas na área de preparação."
       },
       {
        "t": "cod",
        "q": "O que faz este comando?",
        "cod": "git reset --soft HEAD~1",
        "o": [
         "Apaga o último commit e todas as alterações dos ficheiros",
         "Cria um commit que anula o último",
         "Desfaz o último commit mas mantém as alterações na área de preparação",
         "Desfaz o último commit e tira as alterações da área de preparação, mantendo-as nos ficheiros"
        ],
        "c": 2,
        "e": "O --soft só move o ramo para HEAD~1. A área de preparação e os ficheiros ficam como estavam, logo as alterações do commit desfeito continuam preparadas. A última opção descreve o --mixed."
       },
       {
        "t": "cod",
        "q": "O que acontece com este comando?",
        "cod": "git reset --hard HEAD~2",
        "o": [
         "Cria dois commits que anulam os dois últimos",
         "Os dois últimos commits desaparecem, mas as alterações ficam nos ficheiros",
         "Só a área de preparação é limpa",
         "Os dois últimos commits e todas as alterações por commitar são descartados"
        ],
        "c": 3,
        "e": "Com --hard o ramo recua dois commits e a área de preparação e os ficheiros são repostos para coincidirem com esse commit. É por isso que é o modo destrutivo."
       },
       {
        "t": "cod",
        "q": "O que contém o último commit no fim destes comandos?",
        "cod": "git commit -m \"Adiciona menu\"\ngit add footer.html\ngit commit --amend --no-edit",
        "o": [
         "Dois commits separados: um com o menu e outro com footer.html",
         "Um único commit \"Adiciona menu\" que passa a incluir também footer.html",
         "Um commit com a mensagem em branco",
         "Só o footer.html; o menu é descartado"
        ],
        "c": 1,
        "e": "O --amend substitui o último commit por um novo que junta o que estava nele com o que está preparado. O --no-edit mantém a mensagem original sem abrir o editor."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para tirar index.html da área de preparação.",
        "cod": "git restore ___ index.html",
        "o": [
         "--hard",
         "--soft",
         "--staged",
         "--amend"
        ],
        "c": 2,
        "e": "A opção --staged diz ao restore para actuar na área de preparação em vez dos ficheiros. O --hard e o --soft pertencem ao git reset, e o --amend ao git commit."
       },
       {
        "t": "lac",
        "q": "Completa o código para desfazer o último commit, mantendo as alterações na área de preparação.",
        "cod": "git reset ___ HEAD~1",
        "o": [
         "--hard",
         "--mixed",
         "--amend",
         "--soft"
        ],
        "c": 3,
        "e": "O --soft mantém a área de preparação e os ficheiros. O --mixed (por defeito) também mantém os ficheiros, mas limpa a área de preparação, e o --hard apaga as alterações."
       },
       {
        "t": "cod",
        "q": "A Rita quer anular o último commit, que já está no GitHub e foi partilhado com a equipa, sem reescrever a história. Onde está o problema e qual é a correcção?",
        "cod": "git reset --hard HEAD~1\ngit push --force",
        "o": [
         "O problema é o --force; basta tirá-lo",
         "Reescreve a história já publicada; devia usar git revert HEAD e um push normal",
         "O problema é o HEAD~1; devia ser HEAD~2",
         "Não há problema, é a forma recomendada"
        ],
        "c": 1,
        "e": "Reset --hard seguido de push --force apaga commits no remoto e pode estragar o trabalho dos colegas. O git revert HEAD acrescenta um commit que anula o último e envia-se com um push normal."
       },
       {
        "t": "cod",
        "q": "O Pedro quer descartar as alterações que fez a style.css (ainda não fez git add). Porque é que este comando não resolve e qual é a correcção?",
        "cod": "# style.css foi editado mas nunca foi adicionado\ngit restore --staged style.css",
        "o": [
         "Como o ficheiro não está preparado, --staged não lhe faz nada; devia ser git restore style.css",
         "Falta o --hard no fim",
         "O comando certo é git revert style.css",
         "Devia ser git reset --soft style.css"
        ],
        "c": 0,
        "e": "O --staged só mexe na área de preparação, que aqui não tem nada deste ficheiro. Para descartar alterações nos ficheiros usa-se git restore sem --staged."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve o comando que descarta as alterações ainda não preparadas do ficheiro style.css.",
        "ok": [
         "git restore style.css",
         "git checkout -- style.css",
         "git checkout style.css"
        ],
        "tok": [
         "git",
         "restore",
         "style.css",
         "checkout",
         "--",
         "--staged"
        ],
        "e": "O git restore é o comando moderno (Git 2.23+). A forma antiga, git checkout -- style.css, faz o mesmo e continua a ser aceite."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que tira o ficheiro index.html da área de preparação, mantendo as alterações no ficheiro.",
        "ok": [
         "git restore --staged index.html",
         "git reset HEAD index.html",
         "git reset -- index.html",
         "git reset index.html"
        ],
        "tok": [
         "git",
         "restore",
         "--staged",
         "index.html",
         "reset",
         "--hard",
         "revert"
        ],
        "e": "git restore --staged é a forma moderna. A forma antiga git reset HEAD index.html faz o mesmo, porque um reset com caminhos só altera a área de preparação."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que cria um novo commit a anular o último commit.",
        "ok": [
         "git revert HEAD"
        ],
        "tok": [
         "git",
         "revert",
         "HEAD",
         "reset",
         "--hard",
         "HEAD~1"
        ],
        "e": "O git revert HEAD cria um commit com o efeito contrário ao último, sem apagar nada da história."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que desfaz o último commit mas deixa as alterações na área de preparação.",
        "ok": [
         "git reset --soft HEAD~1",
         "git reset --soft HEAD^"
        ],
        "tok": [
         "git",
         "reset",
         "--soft",
         "HEAD~1",
         "--hard",
         "--mixed",
         "revert"
        ],
        "e": "O --soft move o ramo um commit atrás e deixa a área de preparação e os ficheiros intactos. HEAD~1 e HEAD^ indicam ambos o commit anterior."
       }
      ]
     }
    ]
   },
   {
    "id": "ramos",
    "nome": "Ramos (branches)",
    "ficheiro": "ramos.md",
    "ficha": {
     "oque": "Um ramo (branch) é uma linha de desenvolvimento independente dentro do mesmo repositório. Permite trabalhar numa funcionalidade ou correcção sem mexer no código estável e juntar o resultado depois. Criar ramos no Git é muito barato, porque um ramo é apenas um apontador para um commit.",
     "pontos": [
      [
       "git branch",
       "Lista os ramos locais; com um nome, cria um ramo novo mas não muda para ele."
      ],
      [
       "git switch <ramo>",
       "Muda para outro ramo. Com -c cria o ramo e muda logo para ele (forma antiga: git checkout)."
      ],
      [
       "git merge <ramo>",
       "Junta o <ramo> ao ramo em que estás neste momento."
      ],
      [
       "git branch -d <ramo>",
       "Apaga um ramo já integrado. O -D força o apagamento mesmo sem estar integrado."
      ],
      [
       "HEAD",
       "Apontador para o ramo (ou commit) em que estás neste momento."
      ]
     ],
     "exemplo": "git switch -c login\n# editar ficheiros e fazer commits\ngit switch main\ngit merge login\ngit branch -d login",
     "dica": "O merge faz-se sempre para o ramo actual: muda primeiro para o ramo que vai receber as alterações (normalmente main).",
     "fonte": "Documentação oficial do Git (git-scm.com/docs)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Para que servem os ramos (branches) no Git?",
        "o": [
         "Para guardar cópias de segurança no GitHub",
         "Para trabalhar numa funcionalidade em paralelo sem mexer no código principal",
         "Para apagar commits antigos",
         "Para comprimir o repositório"
        ],
        "c": 1,
        "e": "Cada ramo tem a sua própria linha de commits. Assim podes experimentar ou corrigir sem estragar o ramo principal, e só juntas o trabalho quando estiver pronto."
       },
       {
        "t": "mc",
        "q": "O que é o HEAD no Git?",
        "o": [
         "O primeiro commit do repositório",
         "O ramo remoto no GitHub",
         "O apontador para o ramo ou commit em que estás neste momento",
         "Uma cópia do ficheiro principal"
        ],
        "c": 2,
        "e": "O HEAD indica onde estás. Normalmente aponta para um ramo, e esse ramo aponta para o último commit; é por isso que o próximo commit vai para o ramo actual."
       },
       {
        "t": "mc",
        "q": "Que comando lista os ramos locais?",
        "o": [
         "git branch",
         "git log",
         "git remote",
         "git status"
        ],
        "c": 0,
        "e": "Sem argumentos, git branch lista os ramos locais e marca com um asterisco o ramo actual. O git remote lista remotos, não ramos."
       },
       {
        "t": "mc",
        "q": "O que significa fazer merge de um ramo?",
        "o": [
         "Apagar o ramo",
         "Mudar para esse ramo",
         "Enviar o ramo para o GitHub",
         "Juntar as alterações desse ramo ao ramo actual"
        ],
        "c": 3,
        "e": "O merge integra a história de outro ramo naquele em que estás. O ramo de origem continua a existir até o apagares."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "Depois deste comando, em que ramo estás?",
        "cod": "git branch login",
        "o": [
         "No ramo login, porque foi criado",
         "No mesmo ramo em que estavas; o ramo login foi criado mas não ficou activo",
         "Num ramo temporário sem nome",
         "Em nenhum ramo (HEAD destacado)"
        ],
        "c": 1,
        "e": "O git branch <nome> só cria o ramo. Para passares para ele tens de usar git switch login, ou então criar e mudar de uma só vez com git switch -c login."
       },
       {
        "t": "cod",
        "q": "O que faz este comando?",
        "cod": "git switch -c login",
        "o": [
         "Muda para o ramo login, que já tinha de existir",
         "Apaga o ramo login",
         "Cria o ramo login e muda para ele",
         "Junta o ramo login ao actual"
        ],
        "c": 2,
        "e": "O -c (de create) cria o ramo a partir do commit actual e muda logo para ele. Sem -c, o switch só muda para ramos que já existem."
       },
       {
        "t": "cod",
        "q": "O que faz este código?",
        "cod": "git switch main\ngit merge login",
        "o": [
         "Junta main a login, ficando em login",
         "Apaga login depois de o juntar",
         "Cria um novo ramo chamado main",
         "Muda para main e junta nele o ramo login"
        ],
        "c": 3,
        "e": "O merge integra o ramo indicado no ramo actual. Como o switch põe o utilizador em main, as alterações de login passam a fazer parte de main."
       },
       {
        "t": "cod",
        "q": "Estás no ramo main e o ramo login tem commits que nunca foram integrados em lado nenhum. O que acontece?",
        "cod": "git switch main\ngit branch -d login",
        "o": [
         "O ramo é apagado e os commits passam para main",
         "O Git recusa apagar e avisa que o ramo não está totalmente integrado",
         "O ramo é apagado sem avisar",
         "O Git muda para o ramo login"
        ],
        "c": 1,
        "e": "O -d é a versão segura: só apaga ramos já integrados, para não perderes trabalho. Se tiveres a certeza de que queres descartar o ramo, usa -D."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para criar o ramo menu e mudar logo para ele.",
        "cod": "git ___ -c menu",
        "o": [
         "branch",
         "merge",
         "switch",
         "commit"
        ],
        "c": 2,
        "e": "É o git switch que tem o -c para criar e mudar de uma vez. No git branch, a opção -c serve para copiar um ramo, o que não muda de ramo."
       },
       {
        "t": "lac",
        "q": "Estás em main e queres integrar o ramo menu. Completa o código.",
        "cod": "git switch main\ngit ___ menu",
        "o": [
         "switch",
         "branch",
         "revert",
         "merge"
        ],
        "c": 3,
        "e": "O git merge menu junta o ramo menu ao ramo actual, que é main. Repetir o switch só mudava de ramo, e o git branch menu apenas criaria um ramo."
       },
       {
        "t": "cod",
        "q": "O Rui está no ramo menu e quer apagar esse mesmo ramo. Qual é o problema?",
        "cod": "git branch -d menu",
        "o": [
         "Não se pode apagar o ramo em que estás; primeiro muda para outro (git switch main)",
         "Falta o --force no fim",
         "O comando certo é git merge -d menu",
         "O ramo menu só pode ser apagado no GitHub"
        ],
        "c": 0,
        "e": "O Git não deixa apagar o ramo actual, porque o HEAD ficaria a apontar para nada. Muda primeiro para outro ramo e depois apaga."
       },
       {
        "t": "cod",
        "q": "A Ana queria fazer o commit no ramo cores. Em que ramo ficou o commit e qual é a correcção?",
        "cod": "git branch cores\ngit add style.css\ngit commit -m \"Novas cores\"",
        "o": [
         "No ramo cores; não há erro",
         "No ramo em que estava antes (git branch não muda de ramo); devia ter usado git switch -c cores",
         "No ramo cores, mas só depois de um merge",
         "Em lado nenhum; o commit foi descartado"
        ],
        "c": 1,
        "e": "O git branch cores cria o ramo mas deixa-te onde estavas, por isso o commit foi parar ao ramo anterior. Com git switch -c cores o ramo é criado e activado de uma vez."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve o comando que cria o ramo menu e muda logo para ele.",
        "ok": [
         "git switch -c menu",
         "git checkout -b menu",
         "git switch --create menu"
        ],
        "tok": [
         "git",
         "switch",
         "-c",
         "menu",
         "checkout",
         "-b",
         "branch"
        ],
        "e": "git switch -c é a forma moderna. A forma antiga, git checkout -b menu, faz o mesmo e continua a ser aceite."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que muda para o ramo main (que já existe).",
        "ok": [
         "git switch main",
         "git checkout main"
        ],
        "tok": [
         "git",
         "switch",
         "main",
         "checkout",
         "merge",
         "-c"
        ],
        "e": "git switch main é o comando moderno e git checkout main a forma antiga. Sem -c, o switch só funciona com ramos existentes."
       },
       {
        "t": "esc",
        "q": "Estás no ramo main. Escreve o comando que junta o ramo menu ao main.",
        "ok": [
         "git merge menu"
        ],
        "tok": [
         "git",
         "merge",
         "menu",
         "switch",
         "branch",
         "main"
        ],
        "e": "Indica-se o ramo a juntar (menu); o ramo que recebe as alterações é o actual, main."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que apaga o ramo menu (já integrado), da forma segura.",
        "ok": [
         "git branch -d menu",
         "git branch --delete menu"
        ],
        "tok": [
         "git",
         "branch",
         "-d",
         "menu",
         "-D",
         "switch",
         "merge"
        ],
        "e": "O -d só apaga se o ramo já estiver integrado. O -D (maiúsculo) apaga sempre, mesmo perdendo commits não integrados."
       }
      ]
     }
    ]
   },
   {
    "id": "enviar",
    "nome": "Guardar e enviar",
    "ficheiro": "enviar.md",
    "ficha": {
     "oque": "Para guardar o teu trabalho com Git, escolhes o que entra no próximo commit (add), registas um ponto na história (commit) e, para partilhar, envias para um repositório remoto (push). Git é a ferramenta que corre no teu computador; GitHub é um serviço online que aloja repositórios Git e facilita a colaboração.",
     "pontos": [
      [
       "git add <ficheiro>",
       "Prepara o ficheiro para o próximo commit. O git status mostra o que está alterado e o que está preparado."
      ],
      [
       "git commit -m \"mensagem\"",
       "Regista um commit com o que está preparado e uma mensagem a descrever a alteração."
      ],
      [
       "git log --oneline",
       "Mostra o histórico de commits, um por linha."
      ],
      [
       "git remote add origin <url>",
       "Liga o repositório local a um remoto chamado origin. O primeiro envio faz-se com git push -u origin main."
      ],
      [
       "git clone <url> / git pull",
       "O clone copia um repositório remoto para o teu computador; o pull traz as alterações novas do remoto e integra-as no ramo actual."
      ],
      [
       ".gitignore",
       "Ficheiro que lista o que o Git deve ignorar (por exemplo node_modules/ ou .env). Não afecta ficheiros que já estejam a ser acompanhados."
      ]
     ],
     "exemplo": "git status\ngit add index.html\ngit commit -m \"Cria a página inicial\"\ngit remote add origin https://github.com/ana/site.git\ngit push -u origin main",
     "dica": "git add e git commit só guardam no teu computador. Nada chega ao GitHub até fazeres git push.",
     "fonte": "Documentação oficial do Git (git-scm.com/docs)"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Qual é a diferença entre Git e GitHub?",
        "o": [
         "São o mesmo programa com nomes diferentes",
         "Git é o sistema de controlo de versões; GitHub é um serviço online que aloja repositórios Git",
         "GitHub é o sistema de controlo de versões; Git é o site",
         "O Git só funciona dentro do GitHub"
        ],
        "c": 1,
        "e": "O Git funciona no teu computador, mesmo sem internet. O GitHub é um serviço que guarda repositórios Git online e acrescenta funcionalidades de colaboração."
       },
       {
        "t": "mc",
        "q": "Para que serve o ficheiro .gitignore?",
        "o": [
         "Para apagar ficheiros do disco",
         "Para guardar a palavra-passe do GitHub",
         "Para listar ficheiros e pastas que o Git não deve acompanhar",
         "Para listar todos os commits"
        ],
        "c": 2,
        "e": "Cada linha é um padrão de ficheiros ou pastas a ignorar, como node_modules/ ou .env. Evita que lixo ou segredos entrem por engano num commit."
       },
       {
        "t": "mc",
        "q": "O que faz git clone <url>?",
        "o": [
         "Copia um repositório remoto para o teu computador",
         "Envia os teus commits para o remoto",
         "Cria um ramo novo",
         "Apaga o repositório remoto"
        ],
        "c": 0,
        "e": "O clone cria uma pasta nova com o repositório inteiro e a sua história, e configura automaticamente o remoto origin a apontar para o URL."
       },
       {
        "t": "mc",
        "q": "Que comando mostra os ficheiros alterados e o que está preparado para o próximo commit?",
        "o": [
         "git log",
         "git remote",
         "git push",
         "git status"
        ],
        "c": 3,
        "e": "O git status é o comando para veres o estado actual: o que está alterado, o que está preparado e o que ainda não é acompanhado. O git log mostra commits já feitos."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "O que acontece depois destes comandos?",
        "cod": "git add index.html\ngit commit -m \"Cria menu\"",
        "o": [
         "O commit é criado só no teu repositório local; nada vai para o GitHub",
         "O commit é enviado para o GitHub",
         "O ficheiro é apagado",
         "Só prepara o ficheiro, sem criar o commit"
        ],
        "c": 0,
        "e": "O git commit regista na história local. Para enviares para o GitHub tens de fazer git push."
       },
       {
        "t": "cod",
        "q": "O que mostra este comando?",
        "cod": "git log --oneline",
        "o": [
         "Só o último commit, com todos os detalhes",
         "Os ficheiros por commitar",
         "Os ramos existentes",
         "O histórico de commits, um por linha, com hash abreviado e mensagem"
        ],
        "c": 3,
        "e": "O --oneline resume cada commit numa linha, com o início do identificador e a mensagem. Dá uma visão rápida da história."
       },
       {
        "t": "cod",
        "q": "O que faz este comando?",
        "cod": "git remote add origin https://github.com/ana/site.git",
        "o": [
         "Cria o repositório site no GitHub",
         "Copia o repositório do GitHub",
         "Regista no repositório local um remoto chamado origin com aquele URL",
         "Envia os commits para o GitHub"
        ],
        "c": 2,
        "e": "Só dá um nome (origin) a um URL no teu repositório local. O repositório tem de já existir no GitHub e os commits só seguem com git push."
       },
       {
        "t": "cod",
        "q": "O que acontece quando executas este comando?",
        "cod": "git pull origin main",
        "o": [
         "Envia os teus commits para o ramo main do GitHub",
         "Traz as alterações de main do remoto origin e integra-as no ramo actual",
         "Apaga o ramo main remoto",
         "Cria um ramo main novo"
        ],
        "c": 1,
        "e": "O pull é um fetch (descarregar as novidades) seguido da integração no ramo actual. É o contrário do push, que envia."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa o código para guardar um commit com a mensagem indicada.",
        "cod": "git ___ -m \"Corrige o menu\"",
        "o": [
         "push",
         "add",
         "clone",
         "commit"
        ],
        "c": 3,
        "e": "A opção -m do git commit recebe a mensagem. O git add prepara ficheiros e o push envia commits, mas nenhum deles usa -m para uma mensagem."
       },
       {
        "t": "lac",
        "q": "Completa o código para enviar o ramo main para origin e definir o upstream.",
        "cod": "git push ___ origin main",
        "o": [
         "-d",
         "-u",
         "-m",
         "-c"
        ],
        "c": 1,
        "e": "O -u (--set-upstream) liga o ramo local ao remoto, e depois bastam git push e git pull sem argumentos. Atenção: o -d apagaria o ramo no remoto."
       },
       {
        "t": "cod",
        "q": "A Marta pôs .env no .gitignore, mas o ficheiro .env já tinha sido commitado e continua a aparecer como alterado. Porquê e qual é a correcção?",
        "cod": "# conteúdo de .gitignore\n.env\n\n$ git status\n\tmodified:   .env",
        "o": [
         "O .gitignore só afecta ficheiros ainda não acompanhados; é preciso git rm --cached .env",
         "O .gitignore só funciona com a linha \\.env",
         "É preciso apagar a pasta .git",
         "O ficheiro tem de se chamar gitignore, sem ponto"
        ],
        "c": 0,
        "e": "Ficheiros que o Git já acompanha continuam a ser vigiados mesmo depois de entrarem no .gitignore. O git rm --cached .env deixa de os acompanhar sem apagar o ficheiro do disco."
       },
       {
        "t": "cod",
        "q": "O Rui editou menu.html e executou estes comandos. Qual é o erro?",
        "cod": "git commit -m \"Muda menu\"\ngit push origin main",
        "o": [
         "Falta git clone antes do push",
         "O push devia vir antes do commit",
         "Falta git add menu.html antes do commit; sem isso o commit não tem nada preparado",
         "A mensagem tem de ter mais de dez caracteres"
        ],
        "c": 2,
        "e": "O commit só regista o que está na área de preparação. Sem git add (ou git commit -a), o Git responde que não há nada preparado e o push não envia nada de novo."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve o comando que prepara o ficheiro index.html para o próximo commit.",
        "ok": [
         "git add index.html"
        ],
        "tok": [
         "git",
         "add",
         "index.html",
         "commit",
         "push",
         "status"
        ],
        "e": "O git add põe o ficheiro na área de preparação, de onde o próximo git commit o vai buscar."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que guarda um commit com a mensagem Primeiro (usa -m).",
        "ok": [
         "git commit -m Primeiro"
        ],
        "tok": [
         "git",
         "commit",
         "-m",
         "Primeiro",
         "add",
         "push",
         "-a"
        ],
        "e": "O -m dá a mensagem directamente na linha de comandos. Se não o usares, o Git abre um editor para a escreveres."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que liga o repositório local ao remoto chamado origin, com o URL https://github.com/ana/site.git",
        "ok": [
         "git remote add origin https://github.com/ana/site.git"
        ],
        "tok": [
         "git",
         "remote",
         "add",
         "origin",
         "https://github.com/ana/site.git",
         "clone",
         "push",
         "-u"
        ],
        "e": "A forma é git remote add <nome> <url>. Por convenção, o remoto principal chama-se origin."
       },
       {
        "t": "esc",
        "q": "Escreve o comando que envia o ramo main para origin e define o upstream (primeiro envio).",
        "ok": [
         "git push -u origin main",
         "git push --set-upstream origin main"
        ],
        "tok": [
         "git",
         "push",
         "-u",
         "origin",
         "main",
         "pull",
         "-d",
         "--force"
        ],
        "e": "O -u (ou --set-upstream) guarda a ligação entre main local e main remoto. Depois disso, git push e git pull dispensam argumentos."
       }
      ]
     }
    ]
   }
  ]
 },
 {
  "id": "meu",
  "nome": "O meu código",
  "cor": "#22d3ee",
  "descricao": "Perguntas sobre pedaços reais dos meus projetos",
  "topicos": [
   {
    "id": "adivinha",
    "nome": "Jogo da Adivinha",
    "ficheiro": "adivinha.js",
    "ficha": {
     "oque": "Jogo em JavaScript em que o computador escolhe um número secreto entre 1 e 50 e tu tentas adivinhá-lo. O script guarda o estado do jogo em variáveis, valida cada palpite, diz se o número é maior ou menor, regista o histórico e tem um botão que alterna entre modo escuro e claro.",
     "pontos": [
      [
       "let numeroSecreto, tentativas, jogoAtivo, modoEscuro",
       "O estado do jogo: o número a adivinhar, o contador, se o jogo ainda decorre (true/false) e o tema atual. Os elementos do HTML são capturados com document.getElementById."
      ],
      [
       "alternarModo()",
       "Inverte modoEscuro com ! e, conforme o valor, faz body.classList.remove('light-mode') ou body.classList.add('light-mode'), mudando também o texto do botão."
      ],
      [
       "gerarNumeroSecreto() e iniciarJogo()",
       "return Math.floor(Math.random() * 50) + 1 devolve um inteiro de 1 a 50. iniciarJogo() guarda-o em numeroSecreto e repõe tentativas, jogoAtivo, textos, histórico e botões."
      ],
      [
       "adivinhar()",
       "Termina cedo (return) se o jogo acabou, se a caixa está vazia ou se Number(valor) é NaN ou fora de 1 a 50. Depois conta a tentativa e compara com === e < para mostrar acertaste, maior ou menor."
      ],
      [
       "adicionarHistorico(palpite, acertou)",
       "Cria um span com a classe tentativa-item correto ou errado, limpa a mensagem inicial na primeira jogada e junta-o à listaTentativas."
      ],
      [
       "addEventListener",
       "Liga cada botão à sua função: clique em adivinharBtn chama adivinhar(), Enter no palpiteInput também, reiniciarBtn chama iniciarJogo() e toggleBtn chama alternarModo()."
      ]
     ],
     "exemplo": "function gerarNumeroSecreto() {\n    return Math.floor(Math.random() * 50) + 1;\n}\n\nconst palpite = Number(valor);\n\nif (palpite === numeroSecreto) {\n    jogoAtivo = false;\n} else if (palpite < numeroSecreto) {\n    mensagem = 'Mais acima! Tente um número maior.';\n}",
     "dica": "Usa === para comparar e = só para atribuir: if (palpite = numeroSecreto) não compara nada, altera o palpite.",
     "fonte": "O meu código: adivinha.js"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Para que serve a variável jogoAtivo no teu jogo?",
        "o": [
         "Guarda quantas tentativas já fizeste.",
         "É true enquanto o jogo decorre e passa a false quando acertas, para bloquear mais palpites.",
         "Guarda se o tema está em modo escuro ou claro.",
         "Guarda o número que o computador escolheu."
        ],
        "c": 1,
        "e": "É um booleano (let jogoAtivo = true). Em adivinhar(), if (!jogoAtivo) mostra 'O jogo já acabou!' e faz return; ao acertares, jogoAtivo = false. As tentativas ficam em tentativas, o tema em modoEscuro e o número em numeroSecreto."
       },
       {
        "t": "mc",
        "q": "Para que serve a função gerarNumeroSecreto()?",
        "o": [
         "Pede ao utilizador que escreva o número secreto.",
         "Verifica se o palpite é um número válido.",
         "Mostra o número secreto no ecrã.",
         "Devolve um inteiro aleatório entre 1 e 50, que iniciarJogo() guarda em numeroSecreto."
        ],
        "c": 3,
        "e": "A função só devolve um valor (return). Quem o guarda é iniciarJogo(), na linha numeroSecreto = gerarNumeroSecreto(). Mostrar o número no ecrã só acontece quando acertas."
       },
       {
        "t": "mc",
        "q": "O que faz adivinharBtn.addEventListener('click', function(e) { ... })?",
        "o": [
         "Fica à espera de um clique no botão e, quando acontece, executa a função (e.preventDefault() e adivinhar()).",
         "Executa a função uma vez ao carregar a página, sem esperar por cliques.",
         "Cria um novo botão chamado adivinharBtn.",
         "Muda o texto do botão sempre que escreves um número."
        ],
        "c": 0,
        "e": "addEventListener regista uma função para ser chamada mais tarde, quando o evento ocorre. O parâmetro e é o evento e e.preventDefault() cancela o comportamento por defeito do botão antes de chamares adivinhar()."
       },
       {
        "t": "mc",
        "q": "O que faz a função adicionarHistorico(palpite, acertou)?",
        "o": [
         "Guarda os palpites num ficheiro para a próxima vez que abrires o jogo.",
         "Compara o palpite com o número secreto e diz se é maior ou menor.",
         "Cria um span com o palpite, com a classe correto ou errado, e junta-o à listaTentativas.",
         "Apaga o histórico de tentativas e começa um jogo novo."
        ],
        "c": 2,
        "e": "Usa document.createElement('span'), define a classe com acertou ? 'correto' : 'errado' e faz listaTentativas.appendChild(item). A comparação maior/menor é feita antes, em adivinhar()."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "Que valores pode devolver esta função?",
        "cod": "function gerarNumeroSecreto() {\n    return Math.floor(Math.random() * 50) + 1;\n}",
        "o": [
         "Qualquer inteiro de 1 a 50, incluindo o 1 e o 50.",
         "Qualquer inteiro de 0 a 49.",
         "Qualquer número decimal entre 1 e 51.",
         "Qualquer inteiro de 1 a 51."
        ],
        "c": 0,
        "e": "Math.random() dá um decimal em [0, 1). Vezes 50 fica em [0, 50), Math.floor corta as casas decimais (0 a 49) e o + 1 desloca tudo para 1 a 50."
       },
       {
        "t": "cod",
        "q": "Imagina que palpiteInput.value é '   ' (só espaços). O que acontece neste código?",
        "cod": "const valor = palpiteInput.value.trim();\n\nif (valor === '') {\n    mostrarErro('Digite um número!');\n    return;\n}",
        "o": [
         "Number('   ') dá NaN e aparece 'Digite um número entre 1 e 50!'.",
         "O palpite conta como 0 e a tentativa é registada.",
         "trim() deixa valor como '', por isso aparece 'Digite um número!' e a função termina com return.",
         "O jogo recomeça com um novo número secreto."
        ],
        "c": 2,
        "e": "trim() tira os espaços das pontas, portanto valor fica '' e entra no if. O return interrompe adivinhar(), logo nada mais corre e a tentativa não é contada."
       },
       {
        "t": "cod",
        "q": "valor é '73'. O que acontece neste código?",
        "cod": "const palpite = Number(valor);\n\nif (isNaN(palpite) || palpite < 1 || palpite > 50) {\n    mostrarErro('Digite um número entre 1 e 50!');\n    palpiteInput.value = '';\n    return;\n}\n\ntentativas++;",
        "o": [
         "tentativas passa a 1 e aparece 'Mais abaixo! Tente um número menor.'.",
         "Aparece 'Digite um número entre 1 e 50!', a caixa fica vazia e tentativas++ nem chega a executar.",
         "Number('73') dá NaN, por isso o jogo termina.",
         "Aparece o erro, mas a tentativa é contada na mesma."
        ],
        "c": 1,
        "e": "73 é um número válido para Number (isNaN dá false), mas palpite > 50 é true. O if entra, mostra o erro, limpa a caixa e o return sai antes de tentativas++."
       },
       {
        "t": "cod",
        "q": "Com numeroSecreto = 35 e palpite = 20, qual é o valor final de mensagem?",
        "cod": "if (palpite === numeroSecreto) {\n    mensagem = `PARABÉNS! Acertou o número ${numeroSecreto} em ${tentativas} tentativas!`;\n    jogoAtivo = false;\n} else if (palpite < numeroSecreto) {\n    mensagem = 'Mais acima! Tente um número maior.';\n} else {\n    mensagem = 'Mais abaixo! Tente um número menor.';\n}",
        "o": [
         "PARABÉNS! Acertou o número 35 em 1 tentativas!",
         "Mais abaixo! Tente um número menor.",
         "Continua vazia ('').",
         "Mais acima! Tente um número maior."
        ],
        "c": 3,
        "e": "20 === 35 é false. Depois 20 < 35 é true, por isso entra no else if: o palpite é menor, o número secreto está mais acima. O else só corre se o palpite for maior."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa a linha de alternarModo() que inverte o tema.",
        "cod": "modoEscuro = ___modoEscuro;\nif (modoEscuro) {\n    body.classList.remove('light-mode');\n} else {\n    body.classList.add('light-mode');\n}",
        "o": [
         "!!",
         "not ",
         "!",
         "-"
        ],
        "c": 2,
        "e": "O operador ! nega um booleano: true passa a false e vice-versa. !! deixava o valor igual, 'not' é Python e - transformaria o booleano num número."
       },
       {
        "t": "cod",
        "q": "Uma colega copiou o teu adivinhar(), mas escreveu a comparação assim. Onde está o erro?",
        "cod": "if (palpite = numeroSecreto) {\n    mensagem = 'PARABÉNS!';\n    jogoAtivo = false;\n}",
        "o": [
         "Falta um else a seguir ao if.",
         "O = atribui o valor de numeroSecreto a palpite em vez de comparar; devia ser ===.",
         "jogoAtivo devia ser true.",
         "palpite devia estar entre aspas."
        ],
        "c": 1,
        "e": "Com um só = o palpite passa a ser igual ao número secreto (1 a 50, que conta como verdadeiro), por isso o if entra sempre e o jogador ganha à primeira. Para comparar usa === como no teu código."
       },
       {
        "t": "lac",
        "q": "Completa a linha que conta a tentativa.",
        "cod": "    ___;\n    tentativasEl.textContent = tentativas;",
        "o": [
         "tentativas++",
         "tentativas+1",
         "tentativas--",
         "tentativas = tentativas"
        ],
        "c": 0,
        "e": "tentativas++ soma 1 à variável. tentativas+1 calcula o valor mas não o guarda, tentativas-- diminui, e tentativas = tentativas deixa tudo igual."
       },
       {
        "t": "cod",
        "q": "Depois de acertares e clicares em Novo Jogo, o jogo continua a dizer 'O jogo já acabou!'. Que linha falta em iniciarJogo()?",
        "cod": "function iniciarJogo() {\n    numeroSecreto = gerarNumeroSecreto();\n    tentativas = 0;\n    numeroSecretoEl.textContent = '???';\n    palpiteInput.value = '';\n    palpiteInput.disabled = false;\n    adivinharBtn.disabled = false;\n}",
        "o": [
         "jogoAtivo = false;",
         "modoEscuro = true;",
         "tentativas++;",
         "jogoAtivo = true;"
        ],
        "c": 3,
        "e": "Ao acertares, adivinhar() põe jogoAtivo = false. Se iniciarJogo() não o voltar a pôr a true, o if (!jogoAtivo) bloqueia todos os palpites seguintes, mesmo com a caixa e o botão ativos."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha de gerarNumeroSecreto() que devolve um inteiro aleatório de 1 a 50 (com Math.floor e Math.random).",
        "ok": [
         "return Math.floor(Math.random() * 50) + 1",
         "return Math.floor(Math.random()*50)+1"
        ],
        "tok": [
         "return",
         "Math.floor(Math.random()",
         "*",
         "50)",
         "+",
         "1",
         "Math.round(Math.random()",
         "49)",
         "-"
        ],
        "e": "Math.random() * 50 dá um decimal de 0 a menos de 50; Math.floor corta-o para 0 a 49 e + 1 passa para 1 a 50."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de adivinhar() que converte o texto valor num número com Number e o guarda na constante palpite.",
        "ok": [
         "const palpite = Number(valor)"
        ],
        "tok": [
         "const",
         "palpite",
         "=",
         "Number(valor)",
         "let",
         "parseInt(valor)",
         "valor"
        ],
        "e": "O valor lido de uma caixa de texto é sempre texto. Number(valor) converte-o para número (ou NaN se não der), o que permite as comparações e o isNaN a seguir."
       },
       {
        "t": "esc",
        "q": "Escreve a primeira linha que liga o clique no botão adivinharBtn a uma função com o parâmetro e (usa function, não seta).",
        "ok": [
         "adivinharBtn.addEventListener('click', function(e) {",
         "adivinharBtn.addEventListener('click', function (e) {"
        ],
        "tok": [
         "adivinharBtn.addEventListener('click',",
         "function(e)",
         "{",
         "'keypress',",
         "=>",
         "adivinharBtn.onclick"
        ],
        "e": "addEventListener recebe o nome do evento ('click') e a função a executar. Dentro dela vêm e.preventDefault() e adivinhar()."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de alternarModo() que adiciona a classe 'light-mode' ao body com classList.add.",
        "ok": [
         "body.classList.add('light-mode')"
        ],
        "tok": [
         "body.classList.add('light-mode')",
         "body.classList.remove('light-mode')",
         "body.classList.toggle('light-mode')",
         "body.classList.add('dark-mode')"
        ],
        "e": "A classe light-mode no body ativa as cores do tema claro no CSS. add junta a classe; remove tira-a (modo escuro)."
       }
      ]
     }
    ]
   },
   {
    "id": "i18n",
    "nome": "Tradução do portfólio",
    "ficheiro": "i18n.js",
    "ficha": {
     "oque": "Motor de tradução partilhado por todas as páginas do portfólio, em 4 idiomas (pt, en, es, fr). Os textos vivem no objeto TRADUCOES, o HTML marca os elementos com data-i18n e o código troca o texto consoante o idiomaAtual, que fica guardado no localStorage. Quando o idioma muda, dispara um evento para o script.js refazer o conteúdo gerado por JavaScript.",
     "pontos": [
      [
       "TRADUCOES",
       "Objeto com uma chave por idioma (pt, en, es, fr); dentro de cada um, cada texto tem um nome como nav_perfil ou portfolio_titulo."
      ],
      [
       "detetarIdioma()",
       "Decide o idioma inicial: primeiro o que está guardado em localStorage ('idioma'), depois navigator.languages (só os 2 primeiros caracteres), e por fim 'en'."
      ],
      [
       "t(chave)",
       "Devolve o texto da chave no idioma atual; se faltar, usa o português; se também faltar, devolve a própria chave."
      ],
      [
       "L(obj)",
       "Escolhe o valor certo de um objeto { pt, en, es, fr }, com fallback para pt e depois ''. Usa-se nos dados dos projetos, que têm texto em vários idiomas."
      ],
      [
       "aplicarTraducoes()",
       "Para cada [data-i18n] faz el.innerHTML = t(chave), traduz placeholders e metadados, atualiza os botões de idioma e dispara o evento 'idiomaAlterado'."
      ],
      [
       "mudarIdioma(novoIdioma)",
       "Valida o idioma, atualiza idiomaAtual, grava em localStorage e chama aplicarTraducoes(). O script.js escuta 'idiomaAlterado' para recarregar projetos, experiência e formação."
      ]
     ],
     "exemplo": "let idiomaAtual = detetarIdioma();\n\nfunction t(chave) {\n    return (TRADUCOES[idiomaAtual] && TRADUCOES[idiomaAtual][chave]) || TRADUCOES.pt[chave] || chave;\n}\n\n// HTML: <em data-i18n=\"nav_perfil\">Perfil</em>\nel.innerHTML = t('nav_perfil');",
     "dica": "Como usas innerHTML, os textos podem ter <strong>, mas só deves pôr lá texto teu, nunca texto escrito por um utilizador.",
     "fonte": "O meu código: i18n.js"
    },
    "niveis": [
     {
      "perguntas": [
       {
        "t": "mc",
        "q": "Para que serve o objeto TRADUCOES no i18n.js?",
        "o": [
         "Guarda o idioma que o visitante escolheu.",
         "Lista os idiomas que o site aceita: pt, en, es e fr.",
         "Guarda todos os textos do site, organizados por idioma e por chave (como nav_perfil).",
         "Traduz automaticamente o texto da página."
        ],
        "c": 2,
        "e": "É a 'base de dados' dos textos: TRADUCOES.pt.nav_perfil, TRADUCOES.en.nav_perfil, etc. A lista de idiomas aceites é IDIOMAS_SUPORTADOS, o idioma escolhido é idiomaAtual e quem traduz é aplicarTraducoes()."
       },
       {
        "t": "mc",
        "q": "O que significa data-i18n=\"nav_perfil\" num elemento do HTML?",
        "o": [
         "Diz a aplicarTraducoes() que o texto desse elemento deve ser o t('nav_perfil') no idioma atual.",
         "Define a língua do elemento como 'nav_perfil'.",
         "Cria uma variável JavaScript chamada nav_perfil.",
         "Impede que esse elemento seja traduzido."
        ],
        "c": 0,
        "e": "aplicarTraducoes() faz document.querySelectorAll('[data-i18n]') e, para cada elemento, lê o valor do atributo e põe t(chave) lá dentro. O texto que está no HTML serve só de base até a tradução correr."
       },
       {
        "t": "mc",
        "q": "Para que serve o evento 'idiomaAlterado' disparado no fim de aplicarTraducoes()?",
        "o": [
         "Guarda o novo idioma no localStorage.",
         "Muda o atributo lang da etiqueta html.",
         "Mostra uma mensagem ao visitante a dizer que o idioma mudou.",
         "Avisa o resto do código (o script.js) de que o idioma mudou, para refazer o conteúdo gerado em JavaScript."
        ],
        "c": 3,
        "e": "Projetos, experiência e formação são criados por JavaScript, por isso o data-i18n não os apanha. O script.js faz document.addEventListener('idiomaAlterado', recarregarConteudoDinamico) e volta a gerá-los no novo idioma."
       },
       {
        "t": "mc",
        "q": "Para que serve localStorage.setItem('idioma', novoIdioma) em mudarIdioma()?",
        "o": [
         "Traduz o texto da página para o novo idioma.",
         "Guarda a escolha no navegador, para detetarIdioma() a lembrar na próxima visita.",
         "Envia o idioma escolhido para um servidor.",
         "Muda o idioma do navegador do visitante."
        ],
        "c": 1,
        "e": "O localStorage fica no navegador do visitante e sobrevive ao fecho da página. Na visita seguinte, detetarIdioma() lê localStorage.getItem('idioma') antes de olhar para as preferências do navegador."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "cod",
        "q": "Imagina que idiomaAtual é 'fr' e a chave 'nova_frase' existe só em TRADUCOES.pt, com o texto 'Olá'. O que devolve t('nova_frase')?",
        "cod": "function t(chave) {\n    return (TRADUCOES[idiomaAtual] && TRADUCOES[idiomaAtual][chave]) || TRADUCOES.pt[chave] || chave;\n}",
        "o": [
         "'Olá', o texto em português (fallback).",
         "undefined",
         "'nova_frase', a própria chave.",
         "Dá erro, porque falta a chave em fr."
        ],
        "c": 0,
        "e": "TRADUCOES.fr.nova_frase é undefined, por isso o || passa à alternativa seguinte: TRADUCOES.pt.nova_frase, que existe. A própria chave só é devolvida se faltar também em pt."
       },
       {
        "t": "cod",
        "q": "Com idiomaAtual = 'fr' e obj = { pt: 'Projeto', en: 'Project' }, o que devolve L(obj)?",
        "cod": "function L(obj) {\n    if (!obj) return '';\n    return obj[idiomaAtual] || obj.pt || '';\n}",
        "o": [
         "'Project'",
         "undefined",
         "'Projeto'",
         "''"
        ],
        "c": 2,
        "e": "obj['fr'] é undefined (falso), então o || tenta obj.pt, que é 'Projeto'. O '' final só aparece se faltar também o português."
       },
       {
        "t": "cod",
        "q": "O h2 é <h2 data-i18n=\"portfolio_titulo\">MEUS PROJETOS</h2> e idiomaAtual é 'en'. O que fica escrito no h2 depois deste código?",
        "cod": "document.querySelectorAll('[data-i18n]').forEach(el => {\n    const chave = el.getAttribute('data-i18n');\n    el.innerHTML = t(chave);\n});",
        "o": [
         "MEUS PROJETOS (o texto do HTML não muda).",
         "portfolio_titulo",
         "MIS PROYECTOS",
         "MY PROJECTS"
        ],
        "c": 3,
        "e": "chave vale 'portfolio_titulo' e t() procura-a em TRADUCOES.en, onde o valor é 'MY PROJECTS'. O innerHTML substitui o texto que estava no HTML."
       },
       {
        "t": "cod",
        "q": "Sem nada guardado no localStorage, e com este array de exemplo, que idioma devolve o código?",
        "cod": "const IDIOMAS_SUPORTADOS = ['pt', 'en', 'es', 'fr'];\nconst preferidos = ['de-DE', 'fr-CA', 'en-US'];\nfor (const lingua of preferidos) {\n    const codigo = String(lingua).slice(0, 2).toLowerCase();\n    if (IDIOMAS_SUPORTADOS.includes(codigo)) return codigo;\n}\nreturn 'en';",
        "o": [
         "'de'",
         "'fr'",
         "'en'",
         "'fr-CA'"
        ],
        "c": 1,
        "e": "slice(0, 2) fica só com as 2 primeiras letras. 'de' não está em IDIOMAS_SUPORTADOS, por isso o ciclo continua; 'fr' está, e o return sai logo com 'fr'. O 'en' final só é usado se nenhum servir."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "lac",
        "q": "Completa a linha de mudarIdioma() que guarda a escolha no navegador.",
        "cod": "idiomaAtual = novoIdioma;\nlocalStorage.___('idioma', novoIdioma);\naplicarTraducoes();",
        "o": [
         "getItem",
         "setItem",
         "addItem",
         "putItem"
        ],
        "c": 1,
        "e": "setItem(chave, valor) grava no localStorage. getItem só lê (é o que detetarIdioma() usa) e addItem e putItem não existem na API."
       },
       {
        "t": "cod",
        "q": "Esta versão de t() não tem o teste TRADUCOES[idiomaAtual] &&. Que problema aparece se idiomaAtual for 'de'?",
        "cod": "function t(chave) {\n    return TRADUCOES[idiomaAtual][chave] || TRADUCOES.pt[chave] || chave;\n}",
        "o": [
         "Devolve sempre a chave, sem tentar o português.",
         "Devolve o texto em inglês, que é o idioma por defeito.",
         "Dá TypeError: TRADUCOES['de'] é undefined e não se pode ler [chave] de undefined.",
         "Devolve undefined e a página fica em branco."
        ],
        "c": 2,
        "e": "O && do teu código só avança se TRADUCOES[idiomaAtual] existir; sem ele, TRADUCOES['de'][chave] tenta aceder a uma propriedade de undefined e rebenta antes de chegar ao fallback pt."
       },
       {
        "t": "lac",
        "q": "Completa a linha que avisa o resto do código do idioma atual.",
        "cod": "document.dispatchEvent(new CustomEvent('idiomaAlterado', { detail: ___ }));",
        "o": [
         "TRADUCOES",
         "novoIdioma",
         "idiomaAtual",
         "IDIOMAS_SUPORTADOS"
        ],
        "c": 2,
        "e": "detail leva o idioma atual, que quem escuta lê em evento.detail. novoIdioma só existe dentro de mudarIdioma(), não em aplicarTraducoes()."
       },
       {
        "t": "cod",
        "q": "Nesta versão de aplicarTraducoes(), textos como hero_p1 aparecem com as etiquetas <strong> à vista. Qual é a correcção?",
        "cod": "document.querySelectorAll('[data-i18n]').forEach(el => {\n    const chave = el.getAttribute('data-i18n');\n    el.textContent = t(chave);\n});",
        "o": [
         "Trocar el.textContent por el.value.",
         "Usar el.setAttribute('data-i18n', t(chave)).",
         "Trocar getAttribute por getElementById.",
         "Trocar el.textContent por el.innerHTML."
        ],
        "c": 3,
        "e": "textContent trata tudo como texto simples e mostra as etiquetas à letra. innerHTML interpreta o HTML, por isso o <strong> do hero_p1 fica a negrito, como no teu código."
       }
      ]
     },
     {
      "perguntas": [
       {
        "t": "esc",
        "q": "Escreve a linha que declara, com let, a variável idiomaAtual e lhe atribui o resultado de chamar detetarIdioma().",
        "ok": [
         "let idiomaAtual = detetarIdioma()"
        ],
        "tok": [
         "let",
         "idiomaAtual",
         "=",
         "detetarIdioma()",
         "const",
         "detetarIdioma",
         "'pt'"
        ],
        "e": "É let (e não const) porque mudarIdioma() vai alterar o valor mais tarde. A função detetarIdioma() é chamada logo no arranque."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de L(obj) que devolve o valor do idioma atual, ou o português, ou '' se faltarem os dois.",
        "ok": [
         "return obj[idiomaAtual] || obj.pt || ''"
        ],
        "tok": [
         "return",
         "obj[idiomaAtual]",
         "||",
         "obj.pt",
         "||",
         "''",
         "&&",
         "obj.en",
         "obj[pt]"
        ],
        "e": "Cada || passa à alternativa seguinte quando o valor anterior é falso (undefined ou vazio). Como idiomaAtual muda, usa-se a notação obj[idiomaAtual] com parênteses retos."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de mudarIdioma() que grava em localStorage, com a chave 'idioma', o valor de novoIdioma.",
        "ok": [
         "localStorage.setItem('idioma', novoIdioma)",
         "localStorage.setItem('idioma',novoIdioma)"
        ],
        "tok": [
         "localStorage.setItem('idioma',",
         "novoIdioma)",
         "localStorage.getItem('idioma',",
         "idiomaAtual)",
         "localStorage.setItem(novoIdioma,"
        ],
        "e": "setItem recebe primeiro a chave ('idioma') e depois o valor (novoIdioma). Na visita seguinte, getItem('idioma') devolve esse valor."
       },
       {
        "t": "esc",
        "q": "Escreve a linha de aplicarTraducoes() que põe o atributo lang do elemento html com o idioma atual (usa document.documentElement).",
        "ok": [
         "document.documentElement.lang = idiomaAtual"
        ],
        "tok": [
         "document.documentElement.lang",
         "=",
         "idiomaAtual",
         "document.body.lang",
         "idiomaAtual()",
         "'pt'"
        ],
        "e": "document.documentElement é a etiqueta html. Atualizar o lang ajuda leitores de ecrã e motores de pesquisa a saber em que idioma está a página."
       }
      ]
     }
    ]
   }
  ]
 }
];
