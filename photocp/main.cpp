#define _CRT_SECURE_NO_DEPRECATE
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <vector>
#include <sys/types.h>
#include <sys/stat.h>
//#include <unistd.h>
//#include <dirent.h>
#include <windows.h>
#include <time.h>
#include <map>
#include <string>
#include <direct.h>
#include <stdarg.h>

static char *cvs_id="@(#) $Id: main.cpp,v 1.7 2009/02/26 21:39:28 jeb Exp $";

using namespace std;

wchar_t * argv0 = NULL;

///////////////////////////////////////////////////////////////////////////////
// configuration globale
static wchar_t * cfg_source_dir = NULL;
static wchar_t * cfg_jpeg_destination_dir = NULL;
static wchar_t * cfg_raw_destination_dir = NULL;

///////////////////////////////////////////////////////////////////////////////
// gestion encapsulant une chaine de caracteres pour la gestion des path

class path {
	wchar_t * the_path;
	size_t len; // en nombre de caracteres
	size_t alloc; // en nombre de caracteres

	void expand(size_t nb_chars) {
		if(len + nb_chars > alloc) {
			alloc = len;
			wchar_t * the_new_path = (wchar_t*) malloc(sizeof(wchar_t)*(alloc+1));
			memcpy(the_new_path, the_path, len*sizeof(wchar_t));
			memset(the_new_path+len, 0, (1+nb_chars)*sizeof(wchar_t)); 
			wchar_t * the_delete_path = the_path;
			the_path = the_new_path;
			the_new_path = NULL;
			free(the_delete_path);
		}
		len += nb_chars;
	}

public:
	path(void) {
		the_path = NULL;
		len = 0;
		alloc = 0;
	}
	path(wchar_t * str, size_t allocate_more) {
		if(allocate_more < 0) {
			allocate_more = 0;
		}
		len = wcslen(str);
		alloc = len + allocate_more;
		the_path = (wchar_t*) malloc(sizeof(wchar_t)*(alloc+1));
		wcscpy(the_path, str);
		memset(the_path+len, 0, allocate_more+1);
	}
	~path(void) {
		free(the_path);
		the_path = NULL;
	}

	wchar_t * get(void) {
		return the_path;
	}
	wchar_t * release(void) {
		wchar_t * p = the_path;
		the_path = NULL;
		len=0;
		alloc=0;
		return p;
	}

	void add_trailing_backslash_if_necessary(void) {
		if(the_path[len-1] != L'\\') {
			expand(1);
			the_path[len-1] = L'\\';
			the_path[len] = L'\0';
		}
	}
	void remove_trailing_backslash_if_necessary(void) {
		if(the_path[len-1] == L'\\') {
			the_path[len-1] = L'\\';
			len --;
		}

	}

	void init_with_exe_path(wchar_t * argv0) {
		if(the_path != NULL) {
			free(the_path);
			the_path = NULL;
			len = 0;
			alloc = NULL;
		}

		alloc = 260;
		len = 0;
		the_path = (wchar_t*) malloc(sizeof(wchar_t)*(alloc+1));
		memset(the_path, 0, sizeof(wchar_t)*(alloc+1));
		if((!(argv0[0] == L'\\' && argv0[1] == L'\\')) &&
			(!(argv0[1] == L':'))) {
			_wgetcwd(the_path, alloc);
			len = wcslen(the_path);
			add_trailing_backslash_if_necessary();
		}

		wchar_t * p_argv0 = wcsrchr(argv0, L'\\');
		if(p_argv0 != NULL) {
			cat_length(argv0, (int) (p_argv0 - argv0));
		}

	}

	void cat(wchar_t * str) {
		size_t l = wcslen(str);
		expand(l);
		wcscat(the_path, str);
	}

	void cat_length(wchar_t * str, size_t l) {
		expand(l);
		memcpy(the_path+len-l, str, l*sizeof(wchar_t));
		the_path[len] = L'\0';
	}

	void cat_timestamp(time_t tt) {
		struct tm * pt;
		pt = localtime(&tt);

		expand(15);
		_snwprintf(the_path+len-15, 16, L"%4.4d%2.2d%2.2d-%2.2d%2.2d%2.2d",
			pt->tm_year+1900, pt->tm_mon+1, pt->tm_mday,
			pt->tm_hour, pt->tm_min, pt->tm_sec);
	
	}
	void cat_timestamp_YYYY(time_t tt) {
		struct tm * pt;
		pt = localtime(&tt);

		expand(4);
		_snwprintf(the_path+len-4, 5, L"%4.4d%",
			pt->tm_year+1900);

	}
	void cat_timestamp_YYYYMMDD(time_t tt) {
		struct tm * pt;
		pt = localtime(&tt);

		expand(8);
		_snwprintf(the_path+len-8, 9, L"%4.4d%2.2d%2.2d",
			pt->tm_year+1900, pt->tm_mon+1, pt->tm_mday);
	
	}

	bool go_next_subdir_till(wchar_t * dest_dir, size_t dest_dir_length = 0) {
		if(dest_dir_length == 0) {
			dest_dir_length = wcslen(dest_dir);
		}

		if(len >= dest_dir_length) {
			return false;
		}

		size_t st = 0;
		for(st=len+1; st!=dest_dir_length;st++) {
			if(dest_dir[st] == L'\\') {
				break;
			}
		}

		if(st >= dest_dir_length) {
			return false;
		}

		cat_length(dest_dir+len, st-len);

		return true;
	}

	
};


///////////////////////////////////////////////////////////////////////////////
// gestion des erreurs et messages de log
FILE * log_file = NULL;


void log_file_close(void) {
	fclose(log_file);
}


void fatal_error(wchar_t * fmt, ...) {
	va_list v;

	wprintf(L"FATAL: ");
	va_start(v, fmt);
	vwprintf(fmt, v);
	va_end(v);
	wprintf(L"\n");

	if(log_file != NULL) {
		fwprintf(log_file, L"FATAL: ");
		va_start(v, fmt);
		vfwprintf(log_file, fmt, v);
		va_end(v);
		fwprintf(log_file, L"\n");
		fflush(log_file);
	}


	exit(-1);
}

static int error_count = 0;
int get_error_count(void) {
	return error_count;
}
void reset_error_count(void) {
	error_count = 0;
}


void simple_error(wchar_t * fmt, ...) {
	va_list v;

	error_count ++;

	wprintf(L"ERR%6.6d: ", error_count);
	va_start(v, fmt);
	vwprintf(fmt, v);
	va_end(v);
	wprintf(L"\n");

	if(log_file != NULL) {
		fwprintf(log_file, L"ERR%6.6d: ", error_count);
		va_start(v, fmt);
		vfwprintf(log_file, fmt, v);
		va_end(v);
		fwprintf(log_file, L"\n");
		fflush(log_file);
	}

}

void log_msg(wchar_t * fmt, ...) {
	va_list v;

	va_start(v, fmt);
	vwprintf(fmt, v);
	va_end(v);
	wprintf(L"\n");

	if(log_file != NULL) {
		va_start(v, fmt);
		vfwprintf(log_file, fmt, v);
		va_end(v);
		fwprintf(log_file, L"\n");
		fflush(log_file);
	}
}

/* void log_file_init(wchar_t * argv0) {
	path ini_file_path;
	ini_file_path.init_with_exe_path(argv0);
	ini_file_path.add_trailing_backslash_if_necessary();
	ini_file_path.cat(L"jebsync_");
	ini_file_path.cat_timestamp(reference_time);
	ini_file_path.cat(L".txt");

	wprintf(L"LOG: %s\n", ini_file_path.get());
	log_file = _wfopen(ini_file_path.get(), L"w");
	if(log_file == NULL) {
		fatal_error(L"Ouverture du fichier de log '%s' impossible", ini_file_path.get());
	}
} */

///////////////////////////////////////////////////////////////////////////////
// fonctions utilitaires pour obtenir des infos sur les disques

map<wstring, wstring> drives_names;

#define JEBSYNC_MAX_DRV_NAME 64

void drives_names_init(void) {
	unsigned long ul_drives = _getdrives();
	wchar_t drv[4];
	drv[0] = L'A';
	drv[1] = L':';
	drv[2] = L'\\';
	drv[3] = L'\0';

      while (ul_drives) {
         if (ul_drives & 1) {
		wchar_t drv_name[JEBSYNC_MAX_DRV_NAME];
		wchar_t drv_fs_name[64];
		if(GetVolumeInformationW(drv,
					drv_name,
					sizeof(drv_name),
					NULL,
					0,
					0,
					drv_fs_name,
					sizeof(drv_fs_name))
				==0) {
			// erreur mais on passe, pas important : du a une erreur quelconque comme un lecteur CD sans CD
			// DWORD err = GetLastError();
			//printf("erreur lors de l'appel a GetvolumeInformation drv='%s' err=%lu\n", drv, err);
			//exit(-1);
		}
		else {
	 		//printf("'%s' '%s'\n", drv, drv_name);
			drives_names[drv_name] = drv;
		}

	 }

         ++drv[0];
         ul_drives >>= 1;
      }
	
}

void resolve_drive_name_in_path(wchar_t** path) {
	if((*path)[0] != L'{') {
		return;
	}

	wchar_t * p = NULL;
	for(p=*path; *p!=L'\0'; p++) {
		if(*p == L'}') {
			break;
		}
	}

	if(*p==L'\0') {
		return;
	}
	if(p[1] != L'\\') {
		return;
	}
	p++;

	int drv_name_length = ((int) (p-*path))-2;
	if(drv_name_length > JEBSYNC_MAX_DRV_NAME) {
		return;
	}

	wchar_t drv_name[JEBSYNC_MAX_DRV_NAME+1];
	memcpy(drv_name, (*path)+1, drv_name_length*sizeof(wchar_t));
	drv_name[drv_name_length] = L'\0';
	
	map<wstring, wstring>::iterator it = drives_names.find(drv_name);

	if(it == drives_names.end()) {
		return;
	}

	wstring drv = it->second;

	p++;
	int new_path_length = drv.length() + wcslen(p);
	wchar_t * new_path = new wchar_t[new_path_length+1];
	wcscpy(new_path, drv.c_str());
	wcscat(new_path, p);

	wchar_t * delete_path = *path;
	*path=new_path;
	delete[] delete_path;
	delete_path = NULL;
}

///////////////////////////////////////////////////////////////////////////////
// acces aux fichiers

// ces fonctions seront parametrable pour fonctionner aussi bien en local qu'en ftp
// et de façon transparente

time_t convert_file_time(FILETIME ft) {
	unsigned long long ull = ft.dwLowDateTime;
	ull +=ft.dwHighDateTime * 0x100000000;

	//printf("ull=%llu\n", ull);
	//printf("(1970-1601)*(365.25)*24*60*60)=%llu\n", (1970-1601)*(365.25)*24*60*60);
	time_t last_write_time = (time_t) (ull/10000000 - ((1970-1601)*(365)+89.0)*24*60*60);
	return last_write_time;
}

time_t get_file_time(wchar_t * file_name) {
	time_t tt = 0;

	HANDLE h = CreateFileW(file_name,
			GENERIC_READ,
			FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_SHARE_DELETE,
			NULL,
			OPEN_EXISTING,
			0,
			NULL);
	if(h==INVALID_HANDLE_VALUE || h==NULL) {
		DWORD err = GetLastError();
		if(err == ERROR_FILE_NOT_FOUND ||
			err == ERROR_PATH_NOT_FOUND) {
			//printf("file not found %s\n", file_name);
			tt = 0;
			goto end;
		}
		else {
			// printf("Erreur lors de l'accès au fichier '%s' code=%lu\n", file_name, err);
			// exit(-1);
			simple_error(L"Ouverture du fichier '%s' impossible code=%lu", file_name, err);
			tt = (time_t) -1;
		}
	}

	FILETIME creation_time;
	FILETIME last_access_time;
	FILETIME last_write_time;
	creation_time.dwLowDateTime = 0;
	creation_time.dwHighDateTime = 0;
	last_access_time.dwLowDateTime = 0;
	last_access_time.dwHighDateTime = 0;
	last_write_time.dwLowDateTime = 0;
	last_write_time.dwHighDateTime = 0;


	if(GetFileTime(h,
			&creation_time,
			&last_access_time,
			&last_write_time) == 0) {
		DWORD err = GetLastError();
		//printf("Erreur lors de l'accès au fichier '%s' code=%lu (2)\n", file_name, err);
		//exit(-1);
		simple_error(L"Récupération de l'heure du fichier '%s' impossible code=%lu", file_name, err);
		tt = (time_t) -1;
	}

	tt = convert_file_time(last_write_time);

	CloseHandle(h);

end:
	return tt;
}

bool check_dir(wchar_t * dir) {
	bool b = false;
	
	struct _stat s;
	memset(&s, sizeof(s), 0);

	if(_wstat(dir, &s) == 0) {
		if((s.st_mode & S_IFDIR) != 0) {
			b = true;
		}
	}

	return b;
}

bool check_file(wchar_t * file) {
	bool b = false;

	struct _stat s;
	memset(&s, sizeof(s), 0);

	if(_wstat(file, &s) == 0) {
		if((s.st_mode & S_IFREG) != 0) {
			b = true;
		}
	}

	return b;
}

int create_dir(wchar_t * dir_name) {
	if(CreateDirectoryW(dir_name, NULL)==0) {
		DWORD err = GetLastError();
		simple_error(L"erreur lors de la creation du repertoire '%s' code=%lu", dir_name, err);
		return -1;
	}
	return 0;
}

/*int copy_file(wchar_t * source_name, wchar_t * dest_name) {
	if(CopyFileExW(source_name,
			dest_name,
			NULL,
			NULL,
			NULL,
			COPY_FILE_FAIL_IF_EXISTS)
		== 0) {
		DWORD err = GetLastError();
		simple_error(L"erreur lors de la copie du fichier '%s' vers '%s' code=%lu", source_name, dest_name, err);
		return -1;
	}
	return 0;
}*/


int move_file(wchar_t * source_name, wchar_t * dest_name, bool create_dir_if_not_exist, wchar_t * dir_for_creation) {
	if(create_dir_if_not_exist) {
		path dir(dir_for_creation, wcslen(dest_name)-wcslen(dir_for_creation));
		size_t dest_name_length = wcslen(dest_name);
		do {
			if(check_dir(dir.get()) == false) {
				//printf("creation du répertoire '%s'\n", dir);
				if(create_dir(dir.get()) != 0) {
					// nothing. on continue quand meme. ca veut dire qu'on aura des erreurs
					// pour tous les sous-repertoire et pour le fichier
					// c'est le comportement souhaite (on tente quand meme de continuer)
				}
			}
		} while(dir.go_next_subdir_till(dest_name, dest_name_length));
	}

	if(MoveFileExW(source_name,
			dest_name,
			MOVEFILE_COPY_ALLOWED)
		== 0) {
		DWORD err = GetLastError();
		simple_error(L"Erreur lors du deplacement du fichier '%s' vers '%s' code=%lu", source_name, dest_name, err);
		return -1;
	}
	return 0;
}



void list_dir(wchar_t * source_dir,
		wchar_t * jpeg_destination_dir,
		wchar_t * raw_destination_dir,
		wchar_t * subdir,
		bool recursive,
		void (*callback)(wchar_t * source_dir, wchar_t * jpeg_destination_dir, wchar_t * raw_destination_dir,  wchar_t * subdir, wchar_t * file_name, bool is_dir, time_t source_last_modified_date)) {

	vector<wchar_t*> subdir_list;
	path find_str(source_dir, (subdir==NULL?0:wcslen(subdir))+3);
	if(subdir != NULL) {
		find_str.add_trailing_backslash_if_necessary();
		find_str.cat(subdir);
	}
	find_str.add_trailing_backslash_if_necessary();
	find_str.cat(L"*");
	//printf("dbg find_str='%s'\n", find_str);

	WIN32_FIND_DATAW FindFileData;
	HANDLE hFind;
	
	hFind = FindFirstFileW(find_str.get(), &FindFileData);
	if (hFind == INVALID_HANDLE_VALUE) 
	{
		DWORD err = GetLastError();
		simple_error(L"ERREUR LORS DE L'OUVERTURE DU REPERTOIRE '%s' => LA SECTION NE PEUT PAS ETRE TRAITEE code=%lu", find_str, err);
		return;
   	} 

	do {
		if(wcscmp(FindFileData.cFileName, L".") == 0 ||
			wcscmp(FindFileData.cFileName, L"..") == 0) {
			continue;
		}

		time_t last_write_time = convert_file_time(FindFileData.ftLastWriteTime);
		
		callback(source_dir,
			jpeg_destination_dir,
			raw_destination_dir,
			subdir,
		       	FindFileData.cFileName,
			FindFileData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY ? true : false,
			last_write_time);

		if(recursive && 
			((FindFileData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY) != 0)) {
			if(subdir==NULL) {
				subdir_list.push_back(_wcsdup(FindFileData.cFileName));
			}
			else {
				path p(subdir, 1+wcslen(FindFileData.cFileName));
				p.add_trailing_backslash_if_necessary();
				p.cat(FindFileData.cFileName);
				subdir_list.push_back(p.release());
			}
		}

	} while (FindNextFileW(hFind, &FindFileData) != 0);
	
	if(recursive) {
		for(vector<wchar_t*>::iterator it=subdir_list.begin(); it!=subdir_list.end(); it++) {
			list_dir(source_dir, jpeg_destination_dir, raw_destination_dir, (*it), recursive, callback);
		}
		subdir_list.empty();
	}

	// nettoyage
	FindClose(hFind);
	for(vector<wchar_t*>::iterator it=subdir_list.begin(); it!=subdir_list.end(); it++) {
		delete [] (*it);
	}
}

void remove_trailing_backslash(wchar_t * path) {
	size_t len = wcslen(path);
	if(path[len-1] == L'\\') {
		path[len-1] = L'\0';
	}
}


///////////////////////////////////////////////////////////////////////////////
// determination de l'orientation d'une image
int endianess16(bool little_endian, char* buff) {
	int ret = 0;

	if(little_endian) {
		ret = buff[0] + 0x100*buff[1];
	}
	else {
		ret = buff[1] + 0x100*buff[0];
	}

	return ret;
}

size_t endianess32(bool little_endian, char* buff) {
	size_t ret = 0;

	if(little_endian) {
		ret = buff[0] + 0x100*buff[1] + 0x10000*buff[2] + 0x1000000*buff[3];
	}
	else {
		ret = buff[3] + 0x100*buff[2] + 0x10000*buff[1] + 0x1000000*buff[0];
	}

	return ret;
}

int get_tiff_orientation(wchar_t * file_name) {
	int ret = -1;
	char buff[16];
	memset(buff, 0, sizeof(buff));
	bool little_endian = false;
	bool end_flag = false;

	FILE * f = NULL;

	_wfopen_s(&f, file_name, L"rb");
	if(f == NULL) {
		return -1;
	}

	if(fseek(f, 6, SEEK_SET) != 0) {
		return -1;
	}

	size_t st = fread(buff, 1, 6, f);
	if(st != 6) {
		return -1;
	}
	if(buff[0] != 'E' ||
		buff[1] != 'x' ||
		buff[2] != 'i' ||
		buff[3] != 'f' ||
		buff[4] != 0 ||
		buff[5] != 0) {
		return -1;
	}

	st = fread(buff, 1, 8, f);
	if(st != 8) {
		return -1;
	}
	
	if(buff[0] == 'I' && buff[1] == 'I') {
		little_endian = true;

	}
	else if(buff[0] == 'M' && buff[1] == 'M') {
		little_endian = false;
	}
	else {
		return -1;
	}

	if(endianess16(little_endian, buff+2) != 42) {
		return -1;
	}

	size_t ifd_offset = endianess32(little_endian, buff+4);

	do {
	 	if(fseek(f, 12+ifd_offset, SEEK_SET) != 0) {
			return -1;
		}

		st = fread(buff, 1, 2, f);
		if(st != 2) {
			return -1;
		}

		int ifd_entry_count = endianess16(little_endian, buff);

		for(int i=0; i!=ifd_entry_count; i++) {
			st = fread(buff, 1, 12, f);
			if(st != 12) {
				return -1;
			}

			int tag  = endianess16(little_endian, buff);
			int type = endianess16(little_endian, buff+2);
			size_t value_count = endianess32(little_endian, buff+4);

			if(tag==274 && type==3 && value_count==1) {
				if(ret==-1) {
					ret = endianess16(little_endian, buff+8);
					end_flag = true;
					break;
				}
			}

		}
		
		if(end_flag) {
			break;
		}	

		st = fread(buff, 1, 4, f);
		if(st != 4) {
			return -1;
		}
		ifd_offset = endianess32(little_endian, buff);
		log_msg(L"new offset=%u", ifd_offset);
		
	} while(ifd_offset != 0);

	fclose(f);


	return ret;
}


///////////////////////////////////////////////////////////////////////////////
// main
void list_dir_callback(wchar_t * source_dir, wchar_t * jpeg_destination_dir, wchar_t * raw_destination_dir, wchar_t * subdir, wchar_t * file_name, bool is_dir, time_t source_last_modified_date) {
	bool is_raw = false;

	if(is_dir) {
		return;
	}

	size_t file_name_len = wcslen(file_name);
	if(file_name_len < 4) {
		return;
	}
	if(_wcsicmp(file_name+file_name_len-4, L".CR2")!=0) {
		if((_wcsicmp(file_name+file_name_len-4, L".JPG")!=0) && 
			(file_name_len < 5 || _wcsicmp(file_name+file_name_len-4, L".JPEG")!=0)
		)	{ 
			return;
		}
		is_raw = false;
	}
	else {
		is_raw = true;
	}

	wchar_t* destination_dir = (is_raw==true?raw_destination_dir:jpeg_destination_dir);
	
	path from_path(source_dir, 1+(subdir==NULL?0:wcslen(subdir)+1)+wcslen(file_name));
	if(subdir != NULL) {
		from_path.add_trailing_backslash_if_necessary();
		from_path.cat(subdir);
	}
	from_path.add_trailing_backslash_if_necessary();
	from_path.cat(file_name);

	path to_path(destination_dir, 1+4+1+8+1+wcslen(file_name));
	to_path.add_trailing_backslash_if_necessary();
	to_path.cat_timestamp_YYYY(source_last_modified_date);
	to_path.add_trailing_backslash_if_necessary();
	to_path.cat_timestamp_YYYYMMDD(source_last_modified_date);
	to_path.add_trailing_backslash_if_necessary();
	to_path.cat(file_name);

	log_msg(L"   %s", from_path.get());
		
	int orientation = 1;
	if(is_raw == false) {
		orientation = get_tiff_orientation(from_path.get());
	}

	// if null, no nconvert necessary
	wchar_t * nconvert_command = NULL;
	wchar_t * nconvert_command_2 = NULL;
	switch(orientation) {
		case 1:
		// nothing, no nconvert
		break;

		case 2:
		nconvert_command = L"vflip";
		break;

		case 3:
		nconvert_command = L"rot180";
		break;

		case 4:
		nconvert_command = L"rot180";
		nconvert_command_2 = L"vflip";
		break;

		case 5:
		nconvert_command = L"rot270";
		nconvert_command_2 = L"hflip";
		break;

		case 6:
		nconvert_command = L"rot90";
		break;

		case 7:
		nconvert_command = L"rot270";
		nconvert_command_2 = L"vflip";
		break;

		case 8:
		nconvert_command = L"rot270";
		break;

	default:
		// nothing, no nconvert
		break;
	}	

	if(nconvert_command == NULL) {
		log_msg(L"-> %s", to_path.get());
	}
	else if(nconvert_command_2 == NULL) {
		log_msg(L"-> %s (%s)", to_path.get(), nconvert_command);
	}
	else {
		log_msg(L"-> %s (%s %s)",
			to_path.get(), nconvert_command, nconvert_command_2);
	}

	if(move_file(from_path.get(), to_path.get(), true, destination_dir) != 0) {
		
		// nothing
		// message d'erreur déjà affiché
	}
	else {
		if(nconvert_command != NULL) {
			path cmd;
			cmd.init_with_exe_path(argv0);
			cmd.add_trailing_backslash_if_necessary();
			cmd.cat(L"nconvert.exe -quiet -keepfiledate -jpegtrans ");
			cmd.cat(nconvert_command);
			cmd.cat(L" \"");
			cmd.cat(to_path.get());
			cmd.cat(L"\"");

			log_msg(cmd.get());
			if(_wsystem(cmd.get()) != 0) {
				simple_error(L"operation nconvert en erreur");
			}
		}

		if(nconvert_command_2 != NULL) {
			path cmd;
			cmd.init_with_exe_path(argv0);
			cmd.add_trailing_backslash_if_necessary();
			cmd.cat(L"nconvert.exe -quiet -keepfiledate -jpegtrans ");
			cmd.cat(nconvert_command_2);
			cmd.cat(L" \"");
			cmd.cat(to_path.get());
			cmd.cat(L"\"");

			log_msg(cmd.get());
			if(_wsystem(cmd.get()) != 0) {
				simple_error(L"operation nconvert en erreur");
			}
		}
	}
}

void usage(void) {
	printf("photocp <repertoire source> <repertoire destination> [repertoire raw]\n");
	printf("\n");
}

int wmain(int argc, wchar_t * argv[]) {
	if(argc!=3 && argc!=4) {
		usage();
		exit(-1);
	}

	argv0 = argv[0];
	cfg_source_dir = _wcsdup(argv[1]);
	cfg_jpeg_destination_dir = _wcsdup(argv[2]);
	if(argc==4) {
		cfg_raw_destination_dir = _wcsdup(argv[3]);
	}

	//log_file_init(argv[0]);
	log_msg(L"PHOTOCP VERSION 0.0.1");
	log_msg(L"REVISION CVS $Id: main.cpp,v 1.7 2009/02/26 21:39:28 jeb Exp $");
	drives_names_init();
	resolve_drive_name_in_path(&cfg_source_dir);
	resolve_drive_name_in_path(&cfg_jpeg_destination_dir);
	if(cfg_raw_destination_dir!=NULL) {
		resolve_drive_name_in_path(&cfg_raw_destination_dir);
	}
	wchar_t * p = cfg_source_dir + wcslen(cfg_source_dir) - 1;
	if( (*p) == L'\\') {
		*p = L'\0';
	}
	p = cfg_jpeg_destination_dir + wcslen(cfg_source_dir) - 1;
	if( (*p) == L'\\') {
		*p = L'\0';
	}
	p = cfg_raw_destination_dir + wcslen(cfg_source_dir) - 1;
	if( (*p) == L'\\') {
		*p = L'\0';
	}
	if(!check_dir(cfg_source_dir)) {
		fatal_error(L"le repertoire '%s' n'existe pas", cfg_source_dir);
	}
	if(!check_dir(cfg_jpeg_destination_dir)) {
		fatal_error(L"le repertoire '%s' n'existe pas", cfg_jpeg_destination_dir);
	}
	if(cfg_raw_destination_dir!=NULL && !check_dir(cfg_raw_destination_dir)) {
		fatal_error(L"le repertoire '%s' n'existe pas", cfg_raw_destination_dir);
	}


   	log_msg(L"Repertoire source      '%s'", cfg_source_dir);
   	log_msg(L"Repertoire destination '%s'", cfg_jpeg_destination_dir);
   	log_msg(L"Repertoire destination '%s'", (cfg_raw_destination_dir==NULL?L"(aucun)":cfg_raw_destination_dir));
	log_msg(L"");
	list_dir(cfg_source_dir, cfg_jpeg_destination_dir, cfg_raw_destination_dir, NULL, true, list_dir_callback);
	log_msg(L"");
	log_msg(L"Nombre d'erreurs rencontrées : %d", get_error_count());
	log_msg(L"");

	//log_file_close();

	return 0;
}
