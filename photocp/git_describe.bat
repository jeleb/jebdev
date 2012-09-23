git describe > git_describe.h
sed -i "s/^/char * git_describe = \"/" git_describe.h
sed -i "s/$/\";/" git_describe.h