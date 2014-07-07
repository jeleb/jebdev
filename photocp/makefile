
all: bin

!INCLUDE makefile.inc

EXEFILE=$(BINDIR)\photocp.exe
PDBFILE=$(BINDIR)\photocp.pdb

#------------------------------------------------------------------------------
#								   Targets
#------------------------------------------------------------------------------
bin: dir $(EXEFILE)
dir: $(BINDIR) $(OBJDIR) $(RESDIR)

#------------------------------------------------------------------------------
#							     Dépendances
#------------------------------------------------------------------------------
main.cpp: 


#------------------------------------------------------------------------------
#                              Objets à générer
#------------------------------------------------------------------------------
OBJ=	$(OBJDIR)\main.obj


#------------------------------------------------------------------------------
#                          Librairies à linker
#------------------------------------------------------------------------------
!IFNDEF DEBUG
#WINLIBS=libc.lib libcp.lib kernel32.lib user32.lib advapi32.lib\
#		ole32.lib oleaut32.lib uuid.lib gdi32.lib comctl32.lib shell32.lib comdlg32.lib\
#		oleacc.lib Wtsapi32.lib 
WINLIBS=libcmt.lib kernel32.lib user32.lib advapi32.lib\
		ole32.lib oleaut32.lib uuid.lib gdi32.lib comctl32.lib shell32.lib comdlg32.lib\
		oleacc.lib Wtsapi32.lib crypt32.lib
!ELSE
#WINLIBS=libcd.lib libcpd.lib kernel32.lib user32.lib advapi32.lib\
#		ole32.lib oleaut32.lib uuid.lib gdi32.lib comctl32.lib shell32.lib comdlg32.lib\
#		oleacc.lib Wtsapi32.lib 
#WINLIBS=libcmtd.lib kernel32.lib user32.lib advapi32.lib\
#		ole32.lib oleaut32.lib uuid.lib gdi32.lib comctl32.lib shell32.lib comdlg32.lib\
#		oleacc.lib Wtsapi32.lib crypt32.lib
WINLIBS=kernel32.lib user32.lib advapi32.lib\
		ole32.lib oleaut32.lib uuid.lib gdi32.lib comctl32.lib shell32.lib comdlg32.lib\
		oleacc.lib Wtsapi32.lib crypt32.lib
!ENDIF

#------------------------------------------------------------------------------
#                                    Link
#------------------------------------------------------------------------------
$(EXEFILE): makefile makefile.inc $(OBJ)
	set lib=$(PSDKLIB);$(VCLIB);$(BOOSTLIB)
	"$(LINK)" $(LINKOPT) /heap:0x800000,0x40000 $(OBJ) $(WINLIBS)
#	"$(LINK)" $(LINKOPT) /heap:0x800000,0x40000 $(OBJ) $(RESDIR)\resource.res $(WINLIBS)

#------------------------------------------------------------------------------
#                                    Clean
#------------------------------------------------------------------------------
clean:
	-del /q $(OBJDIR) 2>nul
#	-del /q $(RESDIR) 2>nul
	-del /q $(EXEFILE) 2>nul
	-del /q $(PDBFILE) 2>nul
