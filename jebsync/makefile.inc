

VCROOT=C:\Program Files (x86)\Microsoft Visual Studio 10.0\VC
#PSDKROOT=C:\Program Files\Microsoft Platform SDK for Windows Server 2003 R2
PSDKROOT=C:\Program Files\Microsoft SDKs\Windows\v7.1
LIBCURLROOT=libcurl-7.19.3

VCBIN=$(VCROOT)\bin
VCINCLUDE=$(VCROOT)\include
VCLIB=$(VCROOT)\lib

PSDKBIN=$(PSDKROOT)\bin
PSDKINCLUDE=$(PSDKROOT)\include
PSDKLIB=$(PSDKROOT)\lib

LIBCURLINCLUDE=$(LIBCURLROOT)\include
LIBCURLLIB=$(LIBCURLROOT)\lib

CL=$(VCBIN)\cl.exe       # Visual C++ 
LINK=$(VCBIN)\link.exe     # Visual C++
RC=$(PSDKBIN)\rc.exe     # Platform SDK
MIDL=$(PSDKBIN)\midl.exe   # Platform SDK

#------------------------------------------------------------------------------
#                         Chemins Obj, Lib, Bin et Include
#------------------------------------------------------------------------------
!IFNDEF BASEOBJDIR
BASEOBJDIR=..\..\..\file_obj\$(MODULE_PROJECT)\$(MODULE_NAME)
!ENDIF

!IFNDEF DEBUG
OBJDIR=obj\Release
BINDIR=bin\Release
!ELSE
OBJDIR=obj\Debug
BINDIR=bin\Debug
!ENDIF

!IFDEF ENU
BINDIR=$(BINDIR)\ENU
OBJDIR=$(OBJDIR)\ENU
!ELSE
FRA=1
BINDIR=$(BINDIR)\FRA
OBJDIR=$(OBJDIR)\FRA
!ENDIF

#------------------------------------------------------------------------------
#                           Construction arborescence
#------------------------------------------------------------------------------
$(OBJDIR): 
	if not exist "$(OBJDIR)" mkdir "$(OBJDIR)" 

$(BINDIR): 
	if not exist "$(BINDIR)" mkdir "$(BINDIR)" 

#$(RESDIR): 
#	if not exist "$(RESDIR)" mkdir "$(RESDIR)" 

#------------------------------------------------------------------------------
#                            Options des lignes de commande
#------------------------------------------------------------------------------
#CLOPT=	/c /GF /Gy /W4 /WX /EHsc /MDd /Z7 /nologo /Fd$(BINDIR) /Fo$(OBJDIR)^\
CLOPT=	/c /GF /Gy /W4 /WX /EHsc /Z7 /nologo /I"$(PSDKINCLUDE)" /I"$(VCINCLUDE)" /I"$(LIBCURLINCLUDE)" /Fd$(BINDIR) /Fo$(OBJDIR)^\
CLDEF=	/D"WIN32" /D"_WINDOWS" /D"WINVER=0x0600" /D"_WIN32_WINNT=0x0501"

!IFDEF DEBUG
#LINKOPT=/debug\
#		/implib:$(BINDIR)\gpsget.lib\
#		/incremental:no\
#		/machine:IX86\
#		/map:$(OBJDIR)\gpsget.map\
#		/nodefaultlib\
#		/nologo\
#		/opt:ref\
#		/out:$(BINDIR)\gpsget.exe\
#		/pdb:$(BINDIR)\gpsget.pdb\
#		/subsystem:WINDOWS

LINKOPT=/debug\
		/incremental:no\
		/machine:IX86\
		/nologo\
		/opt:ref\
		/out:$(EXEFILE)\
		/pdb:$(PDBFILE)\
		/subsystem:CONSOLE 
		#\
		#/NODEFAULTLIB:libcmt.lib
TESTLINKOPT=/debug\
		/incremental:no\
		/machine:IX86\
		/nologo\
		/opt:ref\
		/out:$(UTESTEXEFILE)\
		/pdb:$(UTESTPDBFILE)\
		/subsystem:CONSOLE 

!ELSE
#LINKOPT=/implib:$(BINDIR)\gpsget.lib\
#		/incremental:no\
#		/machine:IX86\
#		/map:$(OBJDIR)\gpsget.map\
#		/nodefaultlib\
#		/nologo\
#		/opt:ref\
#		/out:$(BINDIR)\gpsdump.exe\
#		/pdb:$(BINDIR)\gpsdump.pdb\
#		/subsystem:WINDOWS

LINKOPT=	/incremental:no\
		/machine:IX86\
		/nologo\
		/opt:ref\
		/out:$(EXEFILE)\
		/pdb:$(PDBFILE)\
		/subsystem:CONSOLE
UTESTLINKOPT=	/incremental:no\
		/machine:IX86\
		/nologo\
		/opt:ref\
		/out:$(UTESTEXEFILE)\
		/pdb:$(UTESTPDBFILE)\
		/subsystem:CONSOLE

!ENDIF
RCOPT=/i"$(PSDKINCLUDE)" /i"$(VCINCLUDE)" /i"$(LIBCURLINCLUDE)"

!IFDEF DEBUG
CLOPT=$(CLOPT) /Od /RTC1
CLDEF=$(CLDEF)
!ELSE
CLOPT=$(CLOPT) /Ox
!ENDIF

!IFDEF ENU
RCOPT=$(RCOPT) /D"AFX_RESOURCE_DLL" /D"AFX_TARG_NEU" /D"AFX_TARG_ENU"
!ENDIF

!IFDEF FRA
RCOPT=$(RCOPT) /D"AFX_RESOURCE_DLL" /D"AFX_TARG_NEU" /D"AFX_TARG_FRA"
!ENDIF

#------------------------------------------------------------------------------
#                        Construction des autres types de fichier
#------------------------------------------------------------------------------
.SUFFIXES:      .cpp .rc 
#.idl 

.cpp{$(OBJDIR)}.obj:
	set include="$(PSDKINCLUDE)";"$(VCINCLUDE)";"$(LIBCURLINCLUDE)"
	"$(CL)" $(CLOPT) $(CLDEF) $<

.rc{$(RESDIR)}.res:
#	set include="$(PSDKINCLUDE)";"$(VCINCLUDE)";"$(LIBCURLINCLUDE)"
	"$(RC)" $(RCOPT) -Fo$@ $<

#.idl.h:
#	"$(MIDL)" $(MIOPT) $<

