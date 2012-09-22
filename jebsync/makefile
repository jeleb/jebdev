
all: bin

!INCLUDE makefile.inc

EXEFILE=$(BINDIR)\jebsync.exe
PDBFILE=$(BINDIR)\jebsync.pdb

UTESTEXEFILE=$(BINDIR)\test_jebsync.exe
UTESTPDBFILE=$(BINDIR)\test_jebsync.pdb

#------------------------------------------------------------------------------
#                                    Functional Tests
#------------------------------------------------------------------------------
.test: bin
	del $<\jebsync.exe
	copy $EXEFILE $<

#------------------------------------------------------------------------------
#								   Targets
#------------------------------------------------------------------------------
bin: dir $(EXEFILE) test
dir: $(BINDIR) $(OBJDIR) $(RESDIR)
utest: dir $(UTESTEXEFILE) test_exec
utest_exec:
	$(UTESTEXEFILE)


#------------------------------------------------------------------------------
#							     Dépendances
#------------------------------------------------------------------------------
main.cpp: lru_cache.cpp
test.cpp: lru_cache.cpp

#------------------------------------------------------------------------------
#                              Objets à générer
#------------------------------------------------------------------------------
OBJ=	$(OBJDIR)\main.obj
UTESTOBJ=	$(OBJDIR)\test.obj

#------------------------------------------------------------------------------
#                          Librairies à linker
#------------------------------------------------------------------------------
!IFNDEF DEBUG
WINLIBS = kernel32.lib curllib.lib Ws2_32.lib Wldap32.lib 

!ELSE
WINLIBS = kernel32.lib curllib.lib Ws2_32.lib Wldap32.lib 

!ENDIF

#------------------------------------------------------------------------------
#                                    Link
#------------------------------------------------------------------------------
$(EXEFILE): makefile makefile.inc $(OBJ)
	set lib=$(PSDKLIB);$(VCLIB);$(LIBCURLLIB)
	"$(LINK)" $(LINKOPT) $(OBJ) $(WINLIBS)

$(UTESTEXEFILE): makefile makefile.inc $(UTESTOBJ)
	set lib=$(PSDKLIB);$(VCLIB);$(LIBCURLLIB)
	"$(LINK)" $(UTESTLINKOPT) $(UTESTOBJ) $(WINLIBS)

#------------------------------------------------------------------------------
#                                    Clean
#------------------------------------------------------------------------------
clean:
	-del /q $(OBJDIR) 2>nul
#	-del /q $(RESDIR) 2>nul
	-del /q $(EXEFILE) 2>nul
	-del /q $(PDBFILE) 2>nul

	
