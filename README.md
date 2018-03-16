# contao_xwebshell
Mit XWebshell können Shellkommandos ausgeführt werden.

Mit "exit" verlassen Sie die Shell und die Session wird gelöscht.

Mit "tl_dump" können Sie die Typolightdatenbank in das aktuelle Verzeichnis dumpen.
Der Name wird automatisch vergeben!    (!!mysqldump muss vom Provider freigegeben sein!!)

Mit "tl_tar" können Sie das aktuelle Verzeichnis packen ( = tar -czvf name.tar.gz .).
Der Name wird automatisch vergeben!

Shellbefehle wie z.B "mc, vi, nano, ftp" etc. funktionieren nicht.
Alle anderen Shellbefehle wie z.B "wget, tar, rm, mkdir, ls -ln" etc. sollte ganz normal funktionieren,
wenn Sie vom Provider freigeschalten wurden.
Mit den Tastaturpfeilen up und down können Sie durch ihre letzen Befehle navigieren.
