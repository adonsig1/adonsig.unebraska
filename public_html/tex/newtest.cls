% document class for tests, quizzes and everything
% based on article class & enumerate enviroment
% written by Allan Donsig, 17 Sept 1997  (adonsig@math.unl.edu)
%       revised, 19 Sept, to work more generally
%	revised again, 20 Feb 2003 to simplify options
%
% identification
\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{newtest}[2003/02/20]

% initial code (default value is false)
\RequirePackage{ifthen} 
\newboolean{ans}
\newboolean{review}

% new variable
% declare options
\DeclareOption{12pt}{\PassOptionsToClass{12pt}{article}}
\DeclareOption{11pt}{\PassOptionsToClass{11pt}{article}}
\DeclareOption{answers}{\setboolean{ans}{true}}
\DeclareOption{review}{\setboolean{review}{true}}

\ProcessOptions

% load class and packages 
\LoadClass{article}
\RequirePackage{fullpage}
\RequirePackage{amssymb}
\RequirePackage{amsmath}
\RequirePackage{epsfig}

% define variables (default value is false)
% for instructions
\newboolean{instr}
% for recitation section
\newboolean{reci}
% for name
\newboolean{name}
% for comments
\newboolean{comments}

%%% main code %%%

% no paragraph indenting
\setlength{\parindent}{0cm}

% define commands for header
\newcommand{\askname}[0]{\setboolean{name}{true}}
\newcommand{\askreci}[0]{\setboolean{reci}{true}}
\newcommand{\qinstr}{}
\newcommand{\qcom}{}
\newcommand{\instructions}[1]{\setboolean{instr}{true}\renewcommand{\qinstr}{#1}}
\newcommand{\comment}[1]{\setboolean{comments}{true}\renewcommand{\qcom}{#1}}
\newcommand{\course}[1]{\newcommand{\qcourse}{#1}}
\newcommand{\header}[1]{\newcommand{\qheader}{#1}}
\newcommand{\testdate}[1]{\newcommand{\qdate}{#1}}


% define commands for questions, questions with points, and 
%      spaces for student answers
\ifthenelse{\boolean{ans}}{
\newcommand{\question}[2]{\item {#1} %
	\begin{quote}\textsl{Solution.} #2 \end{quote} }
\newcommand{\pquestion}[3]{\item {\marginpar{\phantom{pts}.\phantom{pts}%
	#1} #2} \begin{quote}\textsl{Solution.} #3 \end{quote} }
\newcommand{\solspace}[1]{}
\newcommand{\questiononly}[1]{}
}{ \ifthenelse{\boolean{review}}{
\newcommand{\question}[2]{\item {#1} }
\newcommand{\pquestion}[3]{\item {\marginpar{\phantom{pts}.\phantom{pts}%
	 #1} #2} }
\newcommand{\solspace}[1]{}
\newcommand{\questiononly}[1]{#1}
}{
\newcommand{\question}[2]{\item {#1} }
\newcommand{\pquestion}[3]{\item {\marginpar{\phantom{pts}.\phantom{pts}%
	 #1} #2} }
\newcommand{\solspace}[1]{#1}
\newcommand{\questiononly}[1]{#1}
}}


% define command for start
\AtBeginDocument{%
\pagestyle{myheadings}
\markright{\qheader \quad (\qcourse \quad \qdate)}
\thispagestyle{plain}
\reversemarginpar
\ifthenelse{\boolean{ans}}{
\makebox[6.55in]{{\large\bf \qcourse \hfill Answers to \qheader 
	\hfill \qdate} }\vspace{.02in} \hrule
}{
\makebox[6.55in]{{\large\bf \qcourse \hfill \qheader \hfill \qdate} }\vspace{.02in}
\hrule
}
% only ask for name and recitation and give instructions
%       if this is not the answer key 
%	(do not ask for name & reci if this is a review sheet)
\vskip 0.2in
\ifthenelse{\boolean{ans}}{}{
  \ifthenelse{\boolean{review}}{}{
     \ifthenelse{\boolean{name}}{
        \makebox[6.5in]{\large\textbf{Name :}\hrulefill}}{}%
     \ifthenelse{\boolean{reci}}{
        \vskip 0.05in
        \makebox[6.5in]{\large\textbf{Recitation Section :}\hrulefill}}{}%
     }
  \ifthenelse{\boolean{instr}}{
     \vskip 0.05in \makebox[6.5in]{{\large\qinstr}\hfill}}{}%
}
% always give comments
\ifthenelse{\boolean{comments}}{
   \vskip 0.05in \makebox[6.5in]{{\large\qcom}\hfill}}{}%
\vskip -0.3in
\begin{enumerate}
}
% define command for finish
\AtEndDocument{\vfill \end{enumerate}}
