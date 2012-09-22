echo off
set E=..\bin\Release\FRA\jebsync.exe

set local_test_path={LAMOUETTE}\devGit\jebsync\test
set ftp_test_host=ftpperso.free.fr
set ftp_test_dir=/jebsync_test
set ftp_test_user=jeanedmond.bulle
set ftp_test_password=soleil
rem set ftp_test_host=192.168.1.1
rem set ftp_test_dir=/sda/sda1/jebsync_test
rem set ftp_test_user=jeb
rem set ftp_test_password=bonnevaux

FOR /F "usebackq delims==" %%P IN (`dir /AD /B local_*.test`) DO ( 
	echo === TEST %%P ===
	del %%P\jebsync.exe
	copy %E% %%P

	rmdir /s /q %%P\current_test
	robocopy /e /NJH /NJS %%P\initial %%P\current_test /XD CVS
	del %%P\jebsync_*.txt
	del %%P\jebsync.ini
	@echo [%%P] > %%P\jebsync.ini
	@echo source = %local_test_path%\%%P\current_test\source >> %%P\jebsync.ini
	@echo destination = %local_test_path%\%%P\current_test\destination >> %%P\jebsync.ini
	@echo sauvegarde_fichiers_modifies = %local_test_path%\%%P\current_test\backup >> %%P\jebsync.ini
	@echo exclude_dir = CVS >> %%P\jebsync.ini


	%%P\jebsync.exe
	if NOT errorlevel 0 (
		echo test KO : jebsync renvoie une erreur
		EXIT /B 1
	)

	bin\sfk list -sincedir %%P\final -dir %%P\current_test
	bin\sfk list -sincedir %%P\current_test -dir %%P\final
	FOR /F "usebackq delims==" %%Q IN (`bin\sfk list -sincedir %%P\final -dir %%P\current_test`) DO ( 
		@echo TEST %%P KO : resultat incorrect
		EXIT /B 1
	)

	FOR /F "usebackq delims==" %%Q IN (`bin\sfk list -sincedir %%P\current_test -dir %%P\final`) DO (
		@echo TEST %%P KO : resultat incorrect
		EXIT /B 1
	)

	@echo TEST %%P OK
	
)

echo ============
echo   TESTS LOCAL OK
echo ============

FOR /F "usebackq delims==" %%P IN (`dir /AD /B ftp_*.test`) DO ( 
	echo === TEST %%P ===
	del %%P\jebsync.exe
	copy %E% %%P

	del %%P\lftp_upload.txt
	del %%P\lftp_download.txt
	rmdir /s /q %%P\current_test
	mkdir %%P\current_test
	robocopy /e /NJH /NJS %%P\initial\source %%P\current_test\source /XD CVS
	cd %%P
	echo open %ftp_test_user%:%ftp_test_password%@%ftp_test_host% > lftp_upload_cmd.txt
	echo rm -r -f %ftp_test_dir%/current_test >> lftp_upload_cmd.txt
	echo mkdir %ftp_test_dir%/current_test >> lftp_upload_cmd.txt
	echo mirror -R -x CVS initial/destination  %ftp_test_dir%/current_test/destination >> lftp_upload_cmd.txt
	echo mirror -R -x CVS initial/backup  %ftp_test_dir%/current_test/backup >> lftp_upload_cmd.txt
	..\bin\sfk crlf-to-lf lftp_upload_cmd.txt
	..\bin\lftp -f lftp_upload_cmd.txt
	cd ..
	
	
	del %%P\jebsync_*.txt
	del %%P\jebsync.ini
	@echo [%%P] > %%P\jebsync.ini
	@echo source = %local_test_path%\%%P\current_test\source >> %%P\jebsync.ini
	@echo destination = ftp://%ftp_test_host%%ftp_test_dir%/current_test/destination >> %%P\jebsync.ini
	@echo sauvegarde_fichiers_modifies = ftp://%ftp_test_host%%ftp_test_dir%/current_test/backup >> %%P\jebsync.ini
	@echo exclude_dir = CVS >> %%P\jebsync.ini
	@echo ftp_login = %ftp_test_user%:%ftp_test_password% >> %%P\jebsync.ini


	%%P\jebsync.exe
	if NOT errorlevel 0 (
		echo test KO : jebsync renvoie une erreur
		EXIT /B 1
	)

	cd %%P
	echo open %ftp_test_user%:%ftp_test_password%@%ftp_test_host% > lftp_download_cmd.txt
	echo mirror -x CVS %ftp_test_dir%/current_test/destination current_test/destination  >> lftp_download_cmd.txt
	echo mirror -x CVS %ftp_test_dir%/current_test/backup current_test/backup >> lftp_download_cmd.txt
	..\bin\sfk crlf-to-lf lftp_download_cmd.txt
	..\bin\lftp -f lftp_download_cmd.txt
	cd ..


	bin\sfk list -sincechg %%P\final -dir %%P\current_test
	bin\sfk list -sincechg %%P\current_test -dir %%P\final
	FOR /F "usebackq delims==" %%Q IN (`bin\sfk list -sincechg %%P\final -dir %%P\current_test`) DO ( 
		@echo TEST %%P KO : resultat incorrect
		EXIT /B 1
	)

	FOR /F "usebackq delims==" %%Q IN (`bin\sfk list -sincechg %%P\current_test -dir %%P\final`) DO (
		@echo TEST %%P KO : resultat incorrect
		EXIT /B 1
	)

	@echo TEST %%P OK
	
)

echo ============
echo   TESTS FTP OK
echo ============

echo tous les test sont OK

set E=
set test_path=
