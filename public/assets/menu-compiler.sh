#!/bin/sh

export Tmpdir="`mktemp -d /tmp/latexSvgButtons_XXXXXX`" || exit 1

cat math-menu.template.htm \
|awk -- '
  BEGIN { 
    Tmpdir = ENVIRON["Tmpdir"];
  }
  /<svg data-tex="/ {
    Command=$0;
    gsub(/^.*<svg data-tex="/,"",Command);
    gsub(/".*$/,"",Command);
    Texfile = Tmpdir "/symbol.tex";
    Dvifile = Tmpdir "/symbol.dvi";
    Outfile = Tmpdir "/symbol.out";
    Svgfile = Tmpdir "/symbol.svg";
    print "\\documentclass{article}\\pagestyle{empty}\\usepackage[utf8]{inputenc}\\usepackage{lmodern}\\usepackage{amssymb}\\begin{document}\\begin{samepage}$" Command "$\\end{samepage}\\end{document}" > Texfile;
    close(Texfile);
    system("LC_ALL=C latex -halt-on-error -output-directory=\"" Tmpdir "\" \"" Texfile "\" > \"" Outfile "\"  && dvisvgm --no-fonts=1 --exact -p 1 -c 2 -o \"" Svgfile "\" \"" Dvifile "\" >> \"" Outfile "\" 2>&1 ");
    SvgStarted = 0;
    SvgContent = "";
    while( 1 == getline Svgline < Svgfile ) {
      if (match(Svgline, /<svg/) > 0) {
        SvgStarted = 1;
        gsub(/<svg/, "<svg data-tex=\"" Command "\"", Svgline);
      }
      if (SvgStarted == 1) {
        SvgContent = SvgContent Svgline;
      }
    }
    close(Svgfile);
    if (SvgStarted == 0) {
      print "ERROR: Could not create SVG image for " Command "." > "/dev/stderr"
      while( 1 == getline Outline < Outfile ) {
        print Outline > "/dev/stderr"
      }
      exit 1
    }
    system("rm \"" Svgfile "\"");
    gsub(/<svg data-tex=".*<\/svg>/, SvgContent);
    
  }
  {print}
' \
>  math-menu.htm
