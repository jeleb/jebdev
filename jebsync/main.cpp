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
#include <direct.h>
#include <stdarg.h>
#include <string>
#include <iostream>
#include <sstream>

#define CURL_STATICLIB
#include <curl/curl.h>

#include "lru_cache.cpp"
	

static char *cvs_id="@(#) $Id: main.cpp,v 1.17 2009/04/12 20:06:10 jeb Exp $";

static wchar_t ftp_prefix[] = L"ftp://";

using namespace std;
class CFG_ITEM;
int delete_file(wchar_t * file_name);
class ftp_dirlist;
ftp_dirlist* ftp_dirlist_cache_get(wchar_t * url, bool create = false/*, wchar_t * root_dir*/);

///////////////////////////////////////////////////////////////////////////////
// options globales

static bool globalOptionTestOnly    = false;
static bool globalOptionCurlVerbose = false;

static time_t reference_time = 0;

///////////////////////////////////////////////////////////////////////////////
// classe de gestion des chaines de caractere avec liberation automatique
class auto_string {
	char * p;

public:
	auto_string(size_t length) {
		p = new char[length+1];
	}
	auto_string(char * p2) {
		p = p2;
	}

	~auto_string() {
		if(p != NULL) {
			delete [] p;
		}
	}

	char * get() {
		return p;
	}
	char * release() {
		char * p2 = p;
		p = NULL;
		return p2;
	}
};


///////////////////////////////////////////////////////////////////////////////
// gestion encapsulant une chaine de caracteres pour la gestion des path

bool is_ftp_prefix(wchar_t * path) {
	if(wcslen(path) < sizeof(ftp_prefix)-sizeof(ftp_prefix[0])) {
		return false;
	}

	return (memcmp(path, ftp_prefix, sizeof(ftp_prefix)-sizeof(ftp_prefix[0])) == 0);
}
void replace_backslash_for_ftp(wchar_t * ftp_path) {
	for(wchar_t * p=ftp_path; *p!=L'\0'; p++) {
		if((*p) == L'\\') {
			(*p) = L'/';
		}
	}
}


class path {
	wchar_t * the_path;
	size_t len; // en nombre de caracteres
	size_t alloc; // en nombre de caracteres
	bool ftp_path;
	wchar_t sep;

	void set_sep(bool ftp) {
		if(ftp) {
			sep = L'/';
		}
		else {
			sep = L'\\';
		}
	}

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
		ftp_path = false;
		set_sep(ftp_path);
	}
	path(wchar_t * str, size_t allocate_more) {
		if(allocate_more < 0) {
			allocate_more = 0;
		}
		len = wcslen(str);
		alloc = len + allocate_more;
		the_path = (wchar_t*) malloc(sizeof(wchar_t)*(alloc+1));
		wcscpy(the_path, str);
		memset(the_path+len, 0, (allocate_more+1)*sizeof(wchar_t));

		ftp_path = is_ftp_prefix(str);
		set_sep(ftp_path);
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
		ftp_path = false;
		set_sep(ftp_path);
		return p;
	}

	void add_trailing_backslash_if_necessary(void) {
		if(the_path[len-1] != sep) {
			expand(1);
			the_path[len-1] = sep;
			the_path[len] = L'\0';
		}
	}
	void remove_trailing_backslash_if_necessary(void) {
		if(the_path[len-1] == sep) {
			the_path[len-1] = L'\0';
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

		ftp_path = false;
		set_sep(ftp_path);

		alloc = 260;
		len = 0;
		the_path = (wchar_t*) malloc(sizeof(wchar_t)*(alloc+1));
		memset(the_path, 0, sizeof(wchar_t)*(alloc+1));
		if((!(argv0[0] == sep && argv0[1] == sep)) &&
			(!(argv0[1] == L':'))) {
			_wgetcwd(the_path, (int) alloc);
			len = wcslen(the_path);
			add_trailing_backslash_if_necessary();
		}

		wchar_t * p_argv0 = wcsrchr(argv0, sep);
		if(p_argv0 != NULL) {
			cat_length(argv0, (int) (p_argv0 - argv0));
		}

	}

	void cat(wchar_t * str) {
		size_t old_len = len;
		
		size_t l = wcslen(str);
		expand(l);
		wcscat(the_path, str);

		if(ftp_path) {
			replace_backslash_for_ftp(the_path+old_len);
		}
	}

	void cat_length(wchar_t * str, size_t l) {
		size_t old_len = len;

		expand(l);
		memcpy(the_path+len-l, str, l*sizeof(wchar_t));
		the_path[len];

		if(ftp_path) {
			replace_backslash_for_ftp(the_path+old_len);
		}
	}

	void cat_timestamp(time_t tt) {
		struct tm * pt;
		pt = localtime(&tt);

		expand(15);
		_snwprintf(the_path+len-15, 16, L"%4.4d%2.2d%2.2d-%2.2d%2.2d%2.2d",
			pt->tm_year+1900, pt->tm_mon+1, pt->tm_mday,
			pt->tm_hour, pt->tm_min, pt->tm_sec);
	
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
			if(dest_dir[st] == sep) {
				break;
			}
		}

		if(st >= dest_dir_length) {
			return false;
		}

		cat_length(dest_dir+len, st-len);

		return true;
	}

	wstring get_next_subdir(wchar_t * dest_dir, size_t dest_dir_length = 0) {
		if(dest_dir_length == 0) {
			dest_dir_length = wcslen(dest_dir);
		}

		if(len >= dest_dir_length) {
			return L"";
		}

		size_t st = 0;
		for(st=len+1; st!=dest_dir_length;st++) {
			if(dest_dir[st] == sep) {
				break;
			}
		}

		if(st >= dest_dir_length) {
			return wstring(dest_dir+len+1, st-len);
		}

		return wstring(dest_dir+len+1, st-len);
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

void log_file_init(wchar_t * argv0) {
	path ini_file_path;
	ini_file_path.init_with_exe_path(argv0);
	ini_file_path.add_trailing_backslash_if_necessary();
	ini_file_path.cat(L"jebsync_");
	ini_file_path.cat_timestamp(reference_time);
	ini_file_path.cat(L".txt");

	wprintf(L"LOG: %s\n", ini_file_path.get());
	log_file = _wfopen(ini_file_path.get(), L"wb");
	if(log_file == NULL) {
		fatal_error(L"Ouverture du fichier de log '%s' impossible", ini_file_path.get());
	}
}

///////////////////////////////////////////////////////////////////////////////
// classe de conversion de chaines wchar_t en utf8 (char*)
class utf8_string {
	char * utf8;
	size_t alloc;

public :
	utf8_string(wchar_t * wstr) {
		alloc = WideCharToMultiByte(CP_UTF8,
			0,
			wstr,
			-1,
			NULL,
			0,
			NULL,
			NULL);
		if(alloc == 0) {
			fatal_error(L"erreur fatale lors d'une conversion utf8\n");
		}

		utf8 = new char[alloc+1];

		int ret_code = WideCharToMultiByte(CP_UTF8,
			0,
			wstr,
			-1,
			utf8,
			(int) alloc,
			NULL,
			NULL);
		if(ret_code == 0) {
			fatal_error(L"erreur fatale lors d'une conversion utf8\n");
		}
	}

	~utf8_string() {
		if(utf8 != NULL) {
			delete [] utf8;
			utf8 = NULL;
		}
	}

	char * get() {
		return utf8;
	}
	char * release() {
		return utf8;
		utf8 = NULL;
	}
};

class utf8_towcs_string {
	wchar_t * wstr;
	size_t alloc;

public :
	utf8_towcs_string(char * utf8) {
		alloc = MultiByteToWideChar(CP_UTF8,
			0,
			utf8,
			-1,
			NULL,
			0);
		if(alloc == 0) {
			fatal_error(L"erreur fatale lors d'une conversion utf8\n");
		}

		wstr = new wchar_t[alloc+1];

		int ret_code = MultiByteToWideChar(CP_UTF8,
			0,
			utf8,
			-1,
			wstr,
			(int) alloc);
		if(ret_code == 0) {
			fatal_error(L"erreur fatale lors d'une conversion utf8\n");
		}
	}

	~utf8_towcs_string() {
		if(wstr != NULL) {
			delete [] wstr;
			wstr = NULL;
		}
	}

	wchar_t * get() {
		return wstr;
	}
	wchar_t * release() {
		return wstr;
		wstr = NULL;
	}
};

///////////////////////////////////////////////////////////////////////////////
// encapsulation de curl
CURL *curl = NULL;  
struct curl_slist * curl_current_slist = NULL;
static char curl_error_buffer[CURL_ERROR_SIZE];


int my_curl_perform_send(wchar_t * url, void * read_function, void * read_function_ctx) {
	CURLcode result = CURLE_OK;  

	memset(curl_error_buffer, 0, sizeof(curl_error_buffer));
    curl_easy_setopt(curl, CURLOPT_ERRORBUFFER, curl_error_buffer);  
	curl_easy_setopt(curl, CURLOPT_HEADER, 0);  
    curl_easy_setopt(curl, CURLOPT_FOLLOWLOCATION, 1);  
	utf8_string url_utf8(url);
    curl_easy_setopt(curl, CURLOPT_URL, url_utf8.get());
	curl_easy_setopt(curl, CURLOPT_UPLOAD, 1); 
	curl_easy_setopt(curl, CURLOPT_FTP_CREATE_MISSING_DIRS, 1); 
    curl_easy_setopt(curl, CURLOPT_READFUNCTION, read_function);  
    curl_easy_setopt(curl, CURLOPT_READDATA, read_function_ctx);  
	curl_easy_setopt(curl, CURLOPT_QUOTE, curl_current_slist);
    result = curl_easy_perform(curl);

	if(curl_current_slist!=NULL) {
		curl_slist_free_all(curl_current_slist);
		curl_current_slist = NULL;
	}

	if(result == CURLE_OK) {
		return 0;
	}
	else {
		simple_error(L"error lors du transfert FTP (curlcode=%lu) : %S", result, curl_error_buffer);
		return -1;
	}
}
int my_curl_perform_get(wchar_t * url, void * write_function, void * write_function_ctx) {
	CURLcode result = CURLE_OK;  

	memset(curl_error_buffer, 0, sizeof(curl_error_buffer));
    curl_easy_setopt(curl, CURLOPT_ERRORBUFFER, curl_error_buffer);  
	curl_easy_setopt(curl, CURLOPT_HEADER, 0);  
    curl_easy_setopt(curl, CURLOPT_FOLLOWLOCATION, 1);  
	utf8_string url_utf8(url);
    curl_easy_setopt(curl, CURLOPT_URL, url_utf8.get());
	curl_easy_setopt(curl, CURLOPT_UPLOAD, 0); 
	curl_easy_setopt(curl, CURLOPT_FTP_CREATE_MISSING_DIRS, 0); 
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_function);  
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, write_function_ctx);  
    result = curl_easy_perform(curl);

	if(curl_current_slist!=NULL) {
		curl_slist_free_all(curl_current_slist);
		curl_current_slist = NULL;
	}

	if(result == CURLE_OK) {
		return 0;
	}
	else {
		simple_error(L"error lors du transfert FTP (curlcode=%lu) : %S", result, curl_error_buffer);
		return -1;
	}
}

void my_curl_add_to_current_slist(wchar_t * ftp_cmd) {
	utf8_string ftp_cmd_utf8(ftp_cmd);

	curl_current_slist = curl_slist_append(curl_current_slist, ftp_cmd_utf8.get());
}


int ftp_init_connection(wchar_t * userpasswd, bool curl_verbose) {
	if(curl==NULL) {
		curl = curl_easy_init();
		if(curl==NULL) {
			simple_error(L"erreur interne : initialisation de CURL impossible");
			return -1;
		}
	}
	curl_easy_setopt(curl, CURLOPT_VERBOSE, (curl_verbose?1:0));
	// ne serait-il pas meilleur de le convertir en ISO-8859-1 ? a checker avec la norme ftp
	if(userpasswd != NULL) {
		utf8_string userpwd_utf8(userpasswd);
		curl_easy_setopt(curl, CURLOPT_USERPWD, userpwd_utf8.get());
	}

	return 0;
}

void ftp_close_connection() {
	curl_easy_setopt(curl, CURLOPT_USERPWD, NULL);
}

void ftp_cleanup(void) {
	if(curl != NULL) {
		curl_easy_cleanup(curl);  
		curl = NULL;
	}
}

size_t ftp_send_file_read(char *data, size_t size, size_t nmemb, FILE * f)
{  
	if(feof(f)) {
		return 0;
	}

	size_t i = fread(data, 1, size*nmemb, f);
	return i;
}

int ftp_send_file(wchar_t * file_name, wchar_t * url) {
	int ret = 0;

	FILE * f = _wfopen(file_name, L"rb");
	if(f == NULL) {
		simple_error(L"ouverture du fichier '%s' pour transfert FTP impossible", file_name);
		return -1;
	}

	ret = my_curl_perform_send(url, ftp_send_file_read, f);

	fclose(f);
	f = NULL;

	return ret;
}

struct ftp_send_filebuffer_ctx {
	char * buffer;
	size_t buffer_length;
	size_t bytes_sent;
};

int ftp_send_filebuffer_read(char *data, size_t size, size_t nmemb, ftp_send_filebuffer_ctx * ctx)
{  
	if(ctx->bytes_sent >= ctx->buffer_length) {
		return 0;
	}

	size_t st = size*nmemb;
	if(st>(ctx->buffer_length-ctx->bytes_sent)) {
		st = ctx->buffer_length-ctx->bytes_sent;
	}

	memcpy(data, ctx->buffer+ctx->bytes_sent, st);
	ctx->bytes_sent += st;

	return (int) st;
}


int ftp_send_filebuffer(char * buffer, size_t buffer_length, wchar_t * url) {
	ftp_send_filebuffer_ctx ctx;
	ctx.buffer = buffer;
	ctx.buffer_length = buffer_length;
	ctx.bytes_sent = 0;

	return my_curl_perform_send(url, ftp_send_filebuffer_read, &ctx);
}

int ftp_get_filebuffer_write(char *data, size_t size, size_t nmemb, string * buffer)
{  
	if(data==NULL || size==0 || nmemb==0) {
		return 0;
	}

	buffer->append(data, size*nmemb);

	return size*nmemb;
}


int ftp_get_filebuffer(string * buffer, wchar_t * url) {
	return my_curl_perform_get(url, ftp_get_filebuffer_write, buffer);
}

wchar_t * ftp_remove_hostname(wchar_t * url) {
	wchar_t * p = url + wcslen(ftp_prefix);
	for(;(*p)!=L'\0' && (*p)!='/'; p++) 
		;

	return p;
}

///////////////////////////////////////////////////////////////////////////////
// gestion des fichiers de contenu de repertoire (les jebsync_dirlist.txt)
static wchar_t dirlist_filename[] = L"/jebsync_dirlist.txt";


class ftp_dirlist_key : public lru_cache_key {
	wstring dir;
	bool create;
//	const wchar_t * root_dir;

public:
	ftp_dirlist_key(const wchar_t * the_dir, bool the_create/*, const wchar_t * the_root_dir*/) {
		dir = the_dir;
		create = the_create;
//		root_dir = the_root_dir;
	}

	unsigned char * get_key_buff(void) {
		return (unsigned char*) dir.c_str();
	}
	size_t get_key_buff_length(void) {
		return dir.length() * sizeof(wchar_t);
	}

	const wchar_t * get_dir(void) {
		return dir.c_str();
	}
/*	const wchar_t * get_root_dir(void) {
		return root_dir;
	}
	*/
	bool get_create(void) {
		return create;
	}
};

class ftp_dirlist {
public:
	static const int ENTRY_NOTHING = 0;
	static const int ENTRY_FILE    = 1;
	static const int ENTRY_DIR     = 2;

private:
	wchar_t * dirlist_url;
	string content;
	map<wstring, pair<int,time_t>> entry_map;
	bool loaded;

public :
	ftp_dirlist(ftp_dirlist_key * key) {
		loaded = false;
		path p((wchar_t*) key->get_dir(), wcslen(dirlist_filename));
		p.cat(dirlist_filename);
		dirlist_url = p.release();

		/*const wchar_t * root_dir = key.get_root_dir();
		if(root_dir != NULL) {
			wchar_t * p1 = (wchar_t*) wcsrchr(key.get_dir(), L'/');
			wstring parent_dir(key.get_dir(), p1-key.get_dir());
			path p((wchar_t*) key.get_root_dir(), parent_dir.length()-wcslen(key.get_root_dir()));

			ftp_dirlist * prev_fdl = NULL;
			do {
				ftp_dirlist * fdl = ftp_dirlist_cache_get((wchar_t*) parent_dir.c_str(), NULL);
				if(!fdl->is_loaded()) {
					fdl->save();
					if(prev_fdl != NULL) {
						wchar_t * p2 = (wchar_t*) wcsrchr(parent_dir.c_str(), L'/');
						prev_fdl->set_entry(p2+1, ftp_dirlist::ENTRY_DIR, 0);
					}
				}
				wstring next = p.get_next_subdir((wchar_t*) parent_dir.c_str(), parent_dir.length());
				if(fdl->get_entry_type((wchar_t*)next.c_str()) != ftp_dirlist::ENTRY_DIR) {
					if(prev_fdl != NULL) {
						wchar_t * p2 = wcsrchr((wchar_t*) parent_dir.c_str(), L'/');
						prev_fdl->set_entry(p2+1, ftp_dirlist::ENTRY_DIR, 0);
					}
				}
			} while(p.go_next_subdir_till((wchar_t*) parent_dir.c_str(), parent_dir.length()));
		}*/

		if(key->get_create() == false) {
			load();
		}
		else {
			save();
		}
	}
	~ftp_dirlist() {
		if(dirlist_url!=NULL) {
			delete[] dirlist_url;
		}
	}
	wchar_t* get_url() {
		return dirlist_url;
	}

	int load(void) {
		if(ftp_get_filebuffer(&content, dirlist_url) != 0) {
			// erreur deja affichee
			return -1;
		}

		if(decode_content()) {
			// erreur deja affichee
			return -1;
		}
		loaded = true;

		return 0;
	}

	int is_loaded(void) {
		return loaded;
	}

	int save(void) {
		encode_content();

		if(ftp_send_filebuffer((char*) content.c_str(), content.length(), dirlist_url) != 0) {
			// erreur deja affichee
			return -1;
		}

		return 0;
	}

	void encode_content() {
		ostringstream oss;
		map<wstring, pair<int, time_t>>::iterator it;
		for(it=entry_map.begin(); it!=entry_map.end(); it++) {
			oss << (it->second.first==ENTRY_FILE?"f\t":"d\t");
			utf8_string utf8_filename((wchar_t*) (it->first.c_str()));
			oss << utf8_filename.get();
			time_t tt = (it->second.second);
			if(tt != 0) {
				oss << "\t";
				struct tm * pt;
				pt = localtime(&tt);
				char buff[16];
				_snprintf(buff, 16, "%4.4d%2.2d%2.2d-%2.2d%2.2d%2.2d",
					pt->tm_year+1900, pt->tm_mon+1,  pt->tm_mday,
					pt->tm_hour, pt->tm_min, pt->tm_sec);
				oss << buff;
			}
			oss << "\n";
		}
		content = oss.str();
	}
	int decode_content() {
		entry_map.clear();

		size_t i = 0;
		bool end_while = false;
		do {
			if(i>=content.length()) {
				break;
			}
			size_t j = content.find("\n", i);
			if(j==string::npos) {
				j=content.length();
			}
			if(j==i+1) {
				i=j+1;
				continue;
			}

			if(content[i+1] != '\t') {
				simple_error(L"le fichier '%s' contient des erreurs : impossible de connaitre le contenu de ce repertoire", dirlist_url);
				return -1;
			}

			int filetype = 0;
			switch(content[i]) {
				case 'f':
					filetype = ENTRY_FILE;
					break;

				case 'd':
					filetype = ENTRY_DIR;
					break;

				default :
					simple_error(L"le fichier '%s' contient des erreurs : impossible de connaitre le contenu de ce repertoire", dirlist_url);
					return -1;
			}

			time_t tt = 0;
			
			size_t k = j;

			if(filetype == ENTRY_FILE) {						
				k = content.find("\t", i+2);
				if(k==string::npos) {
					simple_error(L"le fichier '%s' contient des erreurs : impossible de connaitre le contenu de ce repertoire", dirlist_url);
					return -1;
				}
				string str_timestamp = content.substr(k+1, j-k-1);
				if(str_timestamp.length() != 15) {
					simple_error(L"le fichier '%s' contient des erreurs : impossible de connaitre le contenu de ce repertoire", dirlist_url);
					return -1;
				}
				if(str_timestamp[0] < '0' || str_timestamp[0] > '9' ||
					str_timestamp[1] < '0' || str_timestamp[1] > '9' ||
					str_timestamp[2] < '0' || str_timestamp[2] > '9' ||
					str_timestamp[3] < '0' || str_timestamp[3] > '9' ||
					str_timestamp[4] < '0' || str_timestamp[4] > '9' ||
					str_timestamp[5] < '0' || str_timestamp[5] > '9' ||
					str_timestamp[6] < '0' || str_timestamp[6] > '9' ||
					str_timestamp[7] < '0' || str_timestamp[7] > '9' ||
					str_timestamp[8] != '-' ||
					str_timestamp[9] < '0' || str_timestamp[9] > '9' ||
					str_timestamp[10] < '0' || str_timestamp[10] > '9' ||
					str_timestamp[11] < '0' || str_timestamp[11] > '9' ||
					str_timestamp[12] < '0' || str_timestamp[12] > '9' ||
					str_timestamp[13] < '0' || str_timestamp[13] > '9' ||
					str_timestamp[14] < '0' || str_timestamp[14] > '9') {
					simple_error(L"le fichier '%s' contient des erreurs : impossible de connaitre le contenu de ce repertoire", dirlist_url);
					return -1;
				}

				struct tm t;
				t.tm_year = 1000*(str_timestamp[0]-'0') + 100*(str_timestamp[1]-'0') + 10*(str_timestamp[2]-'0') + (str_timestamp[3]-'0')  - 1900;
				t.tm_mon  = 10*(str_timestamp[4]-'0')  + (str_timestamp[5]-'0') - 1;
				t.tm_mday = 10*(str_timestamp[6]-'0')  + (str_timestamp[7]-'0');
				t.tm_hour = 10*(str_timestamp[9]-'0')  + (str_timestamp[10]-'0');
				t.tm_min  = 10*(str_timestamp[11]-'0') + (str_timestamp[12]-'0');
				t.tm_sec  = 10*(str_timestamp[13]-'0') + (str_timestamp[14]-'0');
				t.tm_isdst = 0;
				
				tt = mktime(&t);

			}

			string str = content.substr(i+2, k-i-2);

			utf8_towcs_string utf8_str((char*) str.c_str());
			wstring wstr = utf8_str.get();

			pair<int, time_t> my_pair;
			my_pair.first = filetype;
			my_pair.second = tt;
			entry_map[wstr] = my_pair;

			i=j+1;
		} while(end_while == false);

		content = "";
		return 0;
	}

	int get_entry_type(wchar_t * entry_name) {
		map<wstring, pair<int, time_t>>::iterator it = entry_map.find(entry_name);

		if(it==entry_map.end()) {
			return ENTRY_NOTHING;
		}

		return it->second.first;
	}
	time_t get_file_time(wchar_t * entry_name) {
		map<wstring, pair<int, time_t>>::iterator it = entry_map.find(entry_name);

		if(it==entry_map.end()) {
			return 0;
		}

		if(it->second.first != ENTRY_FILE) {
			return 0;
		}

		return it->second.second;
	}

	int set_entry(wchar_t * entry_name, int entry_type, time_t file_tt) {
		entry_map[entry_name].first = entry_type;
		entry_map[entry_name].second = (entry_type==ENTRY_FILE?file_tt:0);

		return save();
	}

	int del_entry(wchar_t * entry_name) {
		entry_map.erase(entry_name);
		return save();
	}


	bool check_parents(wchar_t * root_dir) {
		const wchar_t * p1 = wcsrchr(dirlist_url, L'/');
		wstring dir(dirlist_url, p1-dirlist_url);
		path p(root_dir, dir.length()-wcslen(root_dir));

		ftp_dirlist * prev_fdl = NULL;
		do {
			ftp_dirlist * fdl = ftp_dirlist_cache_get((wchar_t*) dir.c_str());
			if(!fdl->is_loaded()) {
				fdl->save();
				if(prev_fdl != NULL) {
					wchar_t * p2 = (wchar_t*) wcsrchr(dir.c_str(), L'/');
					prev_fdl->set_entry(p2+1, ftp_dirlist::ENTRY_DIR, 0);
				}
			}
			wstring next = p.get_next_subdir((wchar_t*) dir.c_str(), dir.length());
			if(fdl->get_entry_type((wchar_t*)next.c_str()) != ftp_dirlist::ENTRY_DIR) {
				if(prev_fdl != NULL) {
					wchar_t * p2 = wcsrchr((wchar_t*) dir.c_str(), L'/');
					prev_fdl->set_entry(p2+1, ftp_dirlist::ENTRY_DIR, 0);
				}
			}
			prev_fdl = fdl;
		} while(p.go_next_subdir_till((wchar_t*) dir.c_str(), dir.length()));
	}
};

// fonction de gestion du cache
static lru_cache<ftp_dirlist, ftp_dirlist_key> ftp_dirlist_lru_cache(500);
ftp_dirlist* ftp_dirlist_cache_get(wchar_t * url, bool create/*, wchar_t * root_dir*/) {
	ftp_dirlist_key the_key(url, create/*, root_dir*/);
	return ftp_dirlist_lru_cache.cached_get(&the_key);
	// TODO : si on a une erreur de decodage, alors is_loaded=false. cette erreur doit etre geree ici et partout ou cette fonction est appelee
}
/*static list<ftp_dirlist*> ftp_dirlist_last_used_first;
static map<wstring, list<ftp_dirlist*>::iterator> ftp_dirlist_map;
#define FTP_DIRLST_CACHE_SIZE 10

void ftp_dirlist_cache_cleanup(void) {
	for(list<ftp_dirlist*>::iterator it = ftp_dirlist_last_used_first.begin();
		it != ftp_dirlist_last_used_first.end();
		it++) {
		delete (*it);
	}

	ftp_dirlist_last_used_first.clear();
	ftp_dirlist_map.clear();
}

void ftp_dirlist_cache_add(ftp_dirlist* fdl, wchar_t * url) {
	ftp_dirlist_last_used_first.push_front(fdl);
	ftp_dirlist_map[url] = ftp_dirlist_last_used_first.begin();

	// remove the older element if the capacity is exceeded 
	if(ftp_dirlist_last_used_first).size() > FTP_DIRLIST_CACHE_SIZE) {
		ftp_dirlist* fdl_delete = ftp_dirlist_last_used_first.pop_back();
		map<wstring, list<ftp_dirlist*>::iterator>::iterator map_it_erase 
			= ftp_dirlist_map.find(url);
		ftp_dirlist_map.erase(map_it_erase);
		delete fdl_delete;
	}
}

ftp_dirlist* ftp_dirlist_cache_get(wchar_t * url) {
	map<wstring, list<ftp_dirlist*>::iterator>::iterator found 
		= ftp_dirlist_map.find(url);

	if(found != ftp_dirlist_map.end()) {
		// good, entry is already in the cache
		// just think to put it on the top of the last_used list
		list<ftp_dirlist*>::iterator list_it = found->second;
		ftp_dirlist_last_used_first.push_front(*list_it);
		ftp_dirlist_last_used_first.erase(list_it);
		found->second = ftp_dirlist_last_used_first.begin();

		return ftp_dirlist_last_used_first.begin();
	}

	// not found, we must create it and store it in the cache
	ftp_dirlist * new_fdl = new ftp_dirlist(url);
	ftp_dirlist_cache_add(new_fdl);
	return fdl;
}*/



///////////////////////////////////////////////////////////////////////////////
// fichiers de backup a purger

vector<wchar_t*> files_to_purge;

void files_to_purge_add(wchar_t * file_name) {
	files_to_purge.push_back(_wcsdup(file_name));
}

void files_to_purge_empty(void) {
	for(vector<wchar_t*>::iterator it=files_to_purge.begin(); it!=files_to_purge.end(); it++) {
		delete[] (*it);
	}
	files_to_purge.empty();
}

void delete_files_to_purge(void) {
	int count = 0;
	for(vector<wchar_t*>::iterator it=files_to_purge.begin(); it!=files_to_purge.end(); it++) {
		log_msg(L"X %s", (*it));
		if(!globalOptionTestOnly) {
			if(delete_file(*it) != 0) {
				// nothing : en cas d'erreur on ne purge pas ce fichier, tant pis
			}
			else {
				count++;
			}
		}
	}
	log_msg(L"");
	log_msg(L"fichiers de backup purges : %d", count);
}

void check_backup_for_purge(wchar_t * backup_dir, wchar_t * subdir, wchar_t * file_name, int purge_after) {
	// modele de nom de fichier de backup : filename.JSYNC-%4.4d%2.2d%2.2d-%2.2d%2.2d%2.2d 
	path p(L"", wcslen(backup_dir)+1+(subdir==NULL?0:wcslen(subdir)+1)+wcslen(file_name)+8);
	p.cat(backup_dir);
	if(subdir != NULL) {
		p.add_trailing_backslash_if_necessary();
		p.cat(subdir);
	}
	p.add_trailing_backslash_if_necessary();
	p.cat(file_name);
	p.cat(L".JSYNC-*");

	WIN32_FIND_DATAW FindFileData;
	HANDLE hFind;
	
	hFind = FindFirstFileW(p.get(), &FindFileData);
	if (hFind == INVALID_HANDLE_VALUE) {
		DWORD err = GetLastError();
		simple_error(L"erreur lors de la recherche de fichiers a purger patern='%s' code=%lu", p.get(), err);
		return;
   	} 

	do {
		if(wcscmp(FindFileData.cFileName, L".") == 0 ||
			wcscmp(FindFileData.cFileName, L"..") == 0) {
			continue;
		}

		if((FindFileData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY) != 0) {
			continue;
		}

		wchar_t * p3 = p.get()+wcslen(p.get())-wcslen(file_name)-8;
		if(memcmp(FindFileData.cFileName, p3, sizeof(wchar_t)*(wcslen(p3)-1)) != 0) {
			continue;
		}

		wchar_t * p2 = FindFileData.cFileName+wcslen(p3)-1;

		if(wcslen(p2) != 15) {
			continue;
		}

		int i =0;
		for(i=0; i!=8; i++) {
			if(p2[i] < '0' || p2[i] > '9') {
				break;
			}
		}
		if(i!=8) {
			continue;
		}
		if(p2[8] != '-') {
			continue;
		}
		for(i=9; i!=15; i++) {
			if(p2[i] < '0' || p2[i] > '9') {
				break;
			}
		}
		if(i!=15) {
			continue;
		}

		struct tm t;
		t.tm_year = 1000*(p2[0]-'0') + 100*(p2[1]-'0') + 10*(p2[2]-'0') + (p2[3]-'0')  - 1900;
		t.tm_mon  = 10*(p2[4]-'0')  + (p2[5]-'0') - 1;
		t.tm_mday = 10*(p2[6]-'0')  + (p2[7]-'0');
		t.tm_hour = 10*(p2[9]-'0')  + (p2[10]-'0');
		t.tm_min  = 10*(p2[11]-'0') + (p2[12]-'0');
		t.tm_sec  = 10*(p2[13]-'0') + (p2[14]-'0');
		t.tm_isdst = 0;
		
		time_t file_time = mktime(&t);

		purge_after = purge_after;
		file_time=file_time;
		if(reference_time > (file_time + purge_after*24*60*60)) {
			path purge_file(L"", wcslen(backup_dir)+1+(subdir==NULL?0:wcslen(subdir)+1)+wcslen(FindFileData.cFileName));

			purge_file.cat(backup_dir);
			if(subdir != NULL) {
				purge_file.add_trailing_backslash_if_necessary();
				purge_file.cat(subdir);
			}
			purge_file.add_trailing_backslash_if_necessary();
			purge_file.cat(FindFileData.cFileName);

			files_to_purge_add(purge_file.get());
		} 

	} while (FindNextFileW(hFind, &FindFileData) != 0);
	
	// nettoyage
	FindClose(hFind);
}


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
	int new_path_length = (int) (drv.length() + wcslen(p));
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

// ces fonctions seront parametrables pour fonctionner aussi bien en local qu'en ftp
// et de façon transparente

time_t convert_file_time_to_time_t(FILETIME ft) {
	unsigned long long ull = ft.dwLowDateTime;
	ull +=ft.dwHighDateTime * 0x100000000;

	time_t last_write_time = (time_t) (ull/10000000 - ((1970-1601)*(365)+89.0)*24*60*60);
	return last_write_time;
}

FILETIME convert_time_t_to_file_time_t(time_t tt) {
	FILETIME ft;
	ft.dwLowDateTime  = 0;
	ft.dwHighDateTime = 0;

    LONGLONG ll = Int32x32To64(tt, 10000000) + 116444736000000000;
    ft.dwLowDateTime = (DWORD) ll;
    ft.dwHighDateTime = ll >>32;
	
	return ft;
}

void set_file_time_fs(wchar_t * file_name, time_t tt) {

	HANDLE h = NULL;
	FILETIME last_write_time = convert_time_t_to_file_time_t(tt);

	h = CreateFileW(file_name,
			GENERIC_READ|FILE_WRITE_ATTRIBUTES,
			FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_SHARE_DELETE,
			NULL,
			OPEN_EXISTING,
			0,
			NULL);
	if(h==INVALID_HANDLE_VALUE || h==NULL) {
		DWORD err = GetLastError();
		simple_error(L"Ouverture du fichier '%s' impossible code=%lu", file_name, err);
		goto end;
	}

	if(SetFileTime(
		  h,
		  NULL,
		  NULL,
		  &last_write_time
		) == 0 ) {
		DWORD err = GetLastError();
		simple_error(L"SetFileTime de '%s' impossible code=%lu", file_name, err);
		tt = (time_t) -1;
	}

end:
	if(h == NULL ||
		h == INVALID_HANDLE_VALUE) {
		CloseHandle(h);
	}
}

time_t get_file_time_fs(wchar_t * file_name) {
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
			goto end;
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

	tt = convert_file_time_to_time_t(last_write_time);

	CloseHandle(h);

end:
	return tt;
}

bool check_dir_fs(wchar_t * dir) {
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

/* not used
bool check_file_fs(wchar_t * file) {
	bool b = false;

	struct _stat s;
	memset(&s, sizeof(s), 0);

	if(_wstat(file, &s) == 0) {
		if((s.st_mode & S_IFREG) != 0) {
			b = true;
		}
	}

	return b;
}*/

int create_dir_fs(wchar_t * dir_name) {
	if(CreateDirectoryW(dir_name, NULL)==0) {
		DWORD err = GetLastError();
		simple_error(L"erreur lors de la creation du repertoire '%s' code=%lu", dir_name, err);
		return -1;
	}
	return 0;
}

int copy_file_fs(wchar_t * source_name, wchar_t * dest_name) {
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
}


int move_file_fs(wchar_t * source_name, wchar_t * dest_name, bool create_dir_if_not_exist, wchar_t * dir_for_creation) {
	if(create_dir_if_not_exist) {
		path dir(dir_for_creation, wcslen(dest_name)-wcslen(dir_for_creation));
		size_t dest_name_length = wcslen(dest_name);
		do {
			if(check_dir_fs(dir.get()) == false) {
				//printf("creation du répertoire '%s'\n", dir);
				if(create_dir_fs(dir.get()) != 0) {
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

bool file_identifical_fs(wchar_t * src, wchar_t * dst) {
	bool identical = false;
	HANDLE h_src = NULL;
	HANDLE h_dst = NULL;
	
	static int buff_size = 8192;
	char* buff_src = (char*) malloc(buff_size); 
	char* buff_dst = (char*) malloc(buff_size); 
	DWORD read_len_src = 0;
	DWORD read_len_dst = 0;

	h_src = CreateFileW(src,
			GENERIC_READ,
			FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_SHARE_DELETE,
			NULL,
			OPEN_EXISTING,
			0,
			NULL);
	if(h_src==INVALID_HANDLE_VALUE || h_src==NULL) {
		DWORD err = GetLastError();
		simple_error(L"Ouverture du fichier '%s' impossible code=%lu", src, err);
		goto end;
	}
	
	h_dst = CreateFileW(dst,
			GENERIC_READ,
			FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_SHARE_DELETE,
			NULL,
			OPEN_EXISTING,
			0,
			NULL);
	if(h_dst==INVALID_HANDLE_VALUE || h_dst==NULL) {
		DWORD err = GetLastError();
		simple_error(L"Ouverture du fichier '%s' impossible code=%lu", dst, err);
		goto end;
	}
	
	do {
		if(!ReadFile(h_src,
				buff_src,
				buff_size,
				&read_len_src,
				NULL)) {
			DWORD err = GetLastError();
			simple_error(L"Lecture du fichier '%s' impossible code=%lu",src, err);
			goto end;
		}
		
		if(!ReadFile(h_dst,
				buff_dst,
				buff_size,
				&read_len_dst,
				NULL)) {
			DWORD err = GetLastError();
			simple_error(L"Lecture du fichier '%s' impossible code=%lu",dst, err);
			goto end;
		}

		if(read_len_src != read_len_dst) {
			goto end;
		}
		
		if(memcmp(buff_src, buff_dst, read_len_src) != 0) {
			goto end;
		}
	} while(read_len_src != 0);
	
	
	identical = true;
	
end:
	if(h_dst != NULL) {
		CloseHandle(h_dst);
	}
	if(h_dst != NULL) {
		CloseHandle(h_src);
	}
	if(buff_src != NULL) {
		free(buff_src);
	}
	if(buff_dst != NULL) {
		free(buff_dst);
	}

	return identical;
}

int delete_file_fs(wchar_t * file_name) {
	if(DeleteFileW(file_name) == 0) {
		DWORD err = GetLastError();
		simple_error(L"Erreur lors de la suppresion du fichier '%s' code=%lu\n", file_name, err);
		return -1;
	}
	return 0;
}

void list_dir_fs(wchar_t * source_dir,
		/*wchar_t * destination_dir,
		wchar_t * backup_dir,
		int purge_if_older,
		*/
		wchar_t * subdir,
		bool recursive,
		void (*callback)(wchar_t * source_dir, /*wchar_t * destination_dir, wchar_t * backup_dir, int purge_if_older,*/ wchar_t * subdir, wchar_t * file_name, bool is_dir, time_t source_last_modified_date, void * callback, bool *pf_dir_excluded),
		void * context) {

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
			wcscmp(FindFileData.cFileName, L"..") == 0 ||
			_wcsicmp(FindFileData.cFileName, dirlist_filename) == 0) {
			continue;
		}

		time_t last_write_time = convert_file_time_to_time_t(FindFileData.ftLastWriteTime);
		
		bool f_dir_excluded = false;

		callback(source_dir,
			/*destination_dir,
			backup_dir,
			purge_if_older,*/
			subdir,
		       	FindFileData.cFileName,
			FindFileData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY ? true : false,
			last_write_time,
			context,
			&f_dir_excluded);

		if(f_dir_excluded == false &&
			recursive && 
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
			list_dir_fs(source_dir, /*destination_dir, backup_dir, purge_if_older,*/ (*it), recursive, callback, context);
		}
		subdir_list.empty();
	}

	// nettoyage
	FindClose(hFind);
	for(vector<wchar_t*>::iterator it=subdir_list.begin(); it!=subdir_list.end(); it++) {
		delete [] (*it);
	}
}

time_t get_file_time_ftp(wchar_t * file_name) {
	wchar_t * p = wcsrchr(file_name, L'/');
	wstring dir(file_name, p-file_name);
	wstring rfile(p+1);

	ftp_dirlist * fdl = ftp_dirlist_cache_get((wchar_t*) dir.c_str());
	return fdl->get_file_time((wchar_t*) (rfile.c_str()));
}

bool check_dir_ftp(wchar_t * dir, wchar_t * root_dir) {

	if(wcscmp(dir, root_dir) == 0) {
		// we tested at the begining the the root_dir exists ...
		return true;
	}

	path p(root_dir, wcslen(dir)-wcslen(root_dir));
	size_t dir_len = wcslen(dir);

	do {
		ftp_dirlist * fdl = ftp_dirlist_cache_get(p.get()/*dir*/);
		if(fdl == NULL) {
			return false;
		}
		wstring next = p.get_next_subdir(dir, dir_len);
		if(fdl->get_entry_type((wchar_t*)next.c_str()) != ftp_dirlist::ENTRY_DIR) {
			return false;
		}
	} while(p.go_next_subdir_till(dir, dir_len));

	return true;
}
/* not used
bool check_file_ftp(wchar_t * file, wchar_t * root_dir) {
	file=file;
	// d'abord faire un check_dir sur le repertoire
	// puis verifier le fichier
	return 0;
} */
int create_dir_ftp(wchar_t * dir_name) {
	// la commande ftp pour creer un repertoire c'est MKD

	wchar_t * p = wcsrchr(dir_name, L'/');
	wstring parent_dir(dir_name, p-dir_name);
	wstring rdir(p+1); 

	ftp_dirlist * fdl_parent = ftp_dirlist_cache_get((wchar_t*) parent_dir.c_str());
	if(!fdl_parent->is_loaded()) {
		simple_error(L"ERREUR INTERNE : tentative de création du répertoire '%s' alors que le répertoire parent n'existe pas", dir_name);
		return -1;
	}

	//wstring wstr_dir_name(dir_name);
	/*ftp_dirlist * fdl = ftp_dirlist_cache_get(dir_name);
	if(fdl->is_loaded()) {
		simple_error(L"ERREUR INTERNE : tentative de création du répertoire '%s' alors qu'il existe déjà", dir_name);
		return -1;
	}*/

	if(fdl_parent->get_entry_type((wchar_t*) rdir.c_str()) != ftp_dirlist::ENTRY_NOTHING) {
		simple_error(L"ERREUR INTERNE : création du répertoire '%s' impossible : un répertoire ou un fichier du même nom existe déjà", dir_name);
		return -1;
	}
	/*ftp_dirlist * fdl =*/ ftp_dirlist_cache_get(dir_name, true);

	/*wstring ftp_cmd = L"MKD ";
	ftp_cmd += dir_name;
	my_curl_add_to_current_slist((wchar_t*) ftp_cmd.c_str());*/
	fdl_parent->set_entry((wchar_t*) rdir.c_str(), ftp_dirlist::ENTRY_DIR, 0);
	//fdl->save();

	return 0;
}
int ftp_send(wchar_t * fs_source_name, wchar_t * ftp_dest_name) {
	//fs_source_name=fs_source_name;
	//ftp_dest_name=ftp_dest_name;

	time_t tt = get_file_time_fs(fs_source_name);
	if(tt == -1) {
		// l'erreur est deja affichee
		return -1;
	}

	if(ftp_send_file(fs_source_name, ftp_dest_name) != 0) {
		simple_error(L"erreur lors du transfert du fichier '%s'", ftp_dest_name);
		return -1;
	}

	wchar_t * p = wcsrchr(ftp_dest_name, L'/');
	wstring dir(ftp_dest_name, p-ftp_dest_name);
	wstring rfile(p+1);

	ftp_dirlist * fdl = ftp_dirlist_cache_get((wchar_t*) dir.c_str());
	fdl->set_entry((wchar_t*)rfile.c_str(), ftp_dirlist::ENTRY_FILE, tt);

	return 0;
}
int ftp_rename(wchar_t * source_name, wchar_t * dest_name, bool create_dir_if_not_exist, wchar_t * dir_for_creation) {
	/* source_name=source_name;
	dest_name=dest_name;
	create_dir_if_not_exist=create_dir_if_not_exist;
	dir_for_creation=dir_for_creation; */

	if(create_dir_if_not_exist) {
		path dir(dir_for_creation, wcslen(dest_name)-wcslen(dir_for_creation));
		size_t dest_name_length = wcslen(dest_name);
		do {
			// TODO : check_dir_ftp contain the same loop. to avoir this n^2 algorithm we coulddo better
			if(check_dir_ftp(dir.get(), dir_for_creation) == false) {
				//printf("creation du répertoire '%s'\n", dir);
				if(create_dir_ftp(dir.get()) != 0) {
					// nothing. on continue quand meme. ca veut dire qu'on aura des erreurs
					// pour tous les sous-repertoire et pour le fichier
					// c'est le comportement souhaite (on tente quand meme de continuer)
				}
			}
		} while(dir.go_next_subdir_till(dest_name, dest_name_length));
	}

	wstring rnfr_cmd = L"RNFR ";
	rnfr_cmd += ftp_remove_hostname(source_name);
	my_curl_add_to_current_slist((wchar_t*) rnfr_cmd.c_str());

	wstring rnto_cmd = L"RNTO ";
	rnto_cmd += ftp_remove_hostname(dest_name);
	my_curl_add_to_current_slist((wchar_t*) rnto_cmd.c_str());

	wchar_t * p1 = wcsrchr(source_name, L'/');
	wstring dir_from(source_name, p1-source_name);
	ftp_dirlist * fdl_from = ftp_dirlist_cache_get((wchar_t*) dir_from.c_str());
	wchar_t * p2 = wcsrchr(dest_name, L'/');
	wstring dir_dest(dest_name, p2-dest_name);
	ftp_dirlist * fdl_dest = ftp_dirlist_cache_get((wchar_t*) dir_dest.c_str());

	time_t tt_from = fdl_from->get_file_time(p1+1);
	if(fdl_dest->set_entry(p2+1, ftp_dirlist::ENTRY_FILE, tt_from) != 0) {
		// l'erreur a déjà été affichée
		return -1;
	}
	if(fdl_from->del_entry(p1+1) != 0) {
		// l'erreur a déjà été affichée
		return -1;
	}

	return 0;
}


bool check_dir(wchar_t * dir, wchar_t * root_dir) {
	if(is_ftp_prefix(dir)) {
		return check_dir_ftp(dir, root_dir);
	}
	else {
		return check_dir_fs(dir);
	}
}
/* not used
bool check_file(wchar_t * file, wchar_t * root_dir) {
	if(is_ftp_prefix(file)) {
		return check_file_ftp(file, root_dir);
	}
	else {
		return check_file_fs(file);
	}
}*/
time_t get_file_time(wchar_t * file_name) {
	if(is_ftp_prefix(file_name)) {
		return get_file_time_ftp(file_name);
	}
	else {
		return get_file_time_fs(file_name);
	}
}

bool file_identifical(wchar_t * src, wchar_t * dst) {
	if(is_ftp_prefix(src) || is_ftp_prefix(dst)) {
		simple_error(L"erreur interne : fonction file_identifical() appelee avec une source ftp");
		// on retourne false comme si les deux fichiers étaient forcément différents
		return false;
	}
	else {
		return file_identifical_fs(src, dst);
	}
}
void set_file_time(wchar_t * file_name, time_t tt) {
	if(is_ftp_prefix(file_name)) {
		simple_error(L"erreur interne : fonction set_file_time() appelee avec une source ftp");
		//set_file_time_ftp(file_name, tt);
	}
	else {
		set_file_time_fs(file_name, tt);
	}
}

int create_dir(wchar_t * dir_name) {
	if(is_ftp_prefix(dir_name)) {
		return create_dir_ftp(dir_name);
	}
	else {
		return create_dir_fs(dir_name);
	}
}
int copy_file(wchar_t * source_name, wchar_t * dest_name) {
	if(is_ftp_prefix(source_name)) {
		simple_error(L"erreur interne : fonction copy_file() appelee avec une source ftp");
		return -1;
	}
	if(is_ftp_prefix(dest_name)) {
		return ftp_send(source_name, dest_name);
	}
	else {
		return copy_file_fs(source_name, dest_name);
	}
}
int move_file(wchar_t * source_name, wchar_t * dest_name, bool create_dir_if_not_exist, wchar_t * dir_for_creation) {
	if(is_ftp_prefix(source_name) && is_ftp_prefix(dest_name)) {
		return ftp_rename(source_name, dest_name, create_dir_if_not_exist, dir_for_creation);
	}
	else if((!is_ftp_prefix(source_name)) && (!is_ftp_prefix(dest_name))) {
		return move_file_fs(source_name, dest_name, create_dir_if_not_exist, dir_for_creation);
	}
	else {
		simple_error(L"erreur interne : fonction move_file() appelee avec une source ftp et une cible locale (ou inversement)");
		return -1;
	}
}
int delete_file(wchar_t * file_name) {
	if(is_ftp_prefix(file_name)) {
		simple_error(L"erreur interne : fonction delete_file() appelee sur un partage ftp");
		return -1;
	}
	else {
		return delete_file_fs(file_name);
	}
}
void list_dir(wchar_t * source_dir,
		/*wchar_t * destination_dir,
		wchar_t * backup_dir,
		int purge_if_older,*/
		wchar_t * subdir,
		bool recursive,
		void (*callback)(wchar_t * source_dir, /*wchar_t * destination_dir, wchar_t * backup_dir, int purge_if_older,*/ wchar_t * subdir, wchar_t * file_name, bool is_dir, time_t source_last_modified_date, void * context, bool *pf_exclude_dir),
		void * context) {

	if(is_ftp_prefix(source_dir)) {
		simple_error(L"erreur interne : fonction list_dir() appelee sur un partage ftp");
	}
	else {
		return list_dir_fs(source_dir,
			/*destination_dir, 
			backup_dir, 
			purge_if_older,	*/
			subdir, 
			recursive, 
			callback,
			context);
	}
}


///////////////////////////////////////////////////////////////////////////////
// lecture du fichier de configuration

void remove_trailing_backslash(wchar_t * path) {
      size_t len = wcslen(path);
      if(path[len-1] == L'\\') {
              path[len-1] = L'\0';
	  }
}

class CFG_ITEM {
public:
wchar_t * name;
// repertoire source, celui dont les fichiers vont etre backupes
wchar_t * sourceDir; 

// repertoire destination, dans lequel les fichiers seront copies
// ce repertoire peut etre un partage ftp
wchar_t * destDir;
wchar_t * ftp_userpwd;

// repertoire de backup pour les fichiers modifies
// quand un fichier est remplace par une version plus récente dans le
// repertoire destination, on garde l'ancienne version ici
wchar_t * backupDir;

// purge des fichiers conserves dans le répertoire de backup :
// 0 : pas de purge, on conserve tous les fichiers dans le répertoire de backup
// n : quand on copie un fichier dans le répertoire de backup,
//     on efface les fichiers de même nom
//     vieux de plus de n jour
int purgeIfOlder;

// noms de fichiers à exclure
vector<wstring> exclude_files;

// noms de repertoires à exclure
vector<wstring> exclude_dirs;



CFG_ITEM(wchar_t * the_name) {
	name         = _wcsdup(the_name);
	sourceDir    = NULL;
	destDir      = NULL;
	ftp_userpwd  = NULL;
	backupDir    = NULL;
	purgeIfOlder = -1;
}

~CFG_ITEM(void) {
	if(name != NULL) {
		delete [] name;
	}
	if(sourceDir != NULL) {
		delete [] sourceDir;
	}
	if(destDir != NULL) {
		delete [] destDir;
	}
	if(ftp_userpwd != NULL) {
		delete [] ftp_userpwd;
	}
	if(backupDir != NULL) {
		delete [] backupDir;
	}
}

void check(void) {
	// enleve les \ de fin dans les noms de repertoire
	remove_trailing_backslash(sourceDir);
	remove_trailing_backslash(destDir);
	remove_trailing_backslash(backupDir);

	// si un chemin débute par une chaine entre { et }, on cherche s'il existe un disque dur de ce nom et 
	// rectifie le chemin en conséquence
	
	resolve_drive_name_in_path(&sourceDir);
	resolve_drive_name_in_path(&destDir);
	resolve_drive_name_in_path(&backupDir);

	// verifie la présence des champs obligatoires
	
	if(sourceDir == NULL) {
		fatal_error(L"Parametre 'source' manquant dans la section '%s'", name);
	}
	else if(is_ftp_prefix(sourceDir)) {
		fatal_error(L"La source ne peut pas être un partage ftp ('%s')", sourceDir);
	}
	else if(!check_dir(sourceDir, NULL)) {
		fatal_error(L"Le répertoire '%s' n'existe pas ou n'est pas un répertoire", sourceDir);
	}

	if(destDir == NULL) {
		fatal_error(L"Parametre 'destination' manquant dans la section '%s'", name);
	}
	else {
		if(is_ftp_prefix(destDir)) {
			ftp_init_connection(ftp_userpwd, globalOptionCurlVerbose);
			ftp_dirlist * fdl = ftp_dirlist_cache_get(destDir);
			if(fdl->is_loaded() == false) {
				fatal_error(L"Le partage ftp '%s' n'existe pas ou n'est pas initialise. "
					L"Pour l'initialiser, creer un fichier vide %s à l'intérieur de ce partage.",
					destDir, dirlist_filename);
			}
			ftp_close_connection();
		}
		else {
			if(!check_dir(destDir, NULL)) {
				fatal_error(L"le répertoire '%s' n'existe pas ou n'est pas un répertoire", destDir);
			}
		}
	}

	if(backupDir != NULL) {
		if(is_ftp_prefix(backupDir)) {
			ftp_init_connection(ftp_userpwd, globalOptionCurlVerbose);
			if(ftp_dirlist_cache_get(backupDir)->is_loaded() == false) {
				fatal_error(L"Le partage ftp '%s' n'existe pas ou n'est pas initialise. "
					L"Pour l'initialiser, creer un fichier vide %s à l'intérieur de ce partage.",
					backupDir, dirlist_filename);
			}
			ftp_close_connection();
		}
		else {
			if(!check_dir(backupDir, NULL)) {
				fatal_error(L"le répertoire '%s' n'existe pas ou n'est pas un répertoire\n", backupDir);
			}
		}
	}

	if(destDir != NULL &&
		backupDir != NULL) {
		if(is_ftp_prefix(destDir) == true &&
		   is_ftp_prefix(backupDir) == false) {
			fatal_error(L"la destination '%s' est un partage ftp mais le repertoire de backup '%s' n'est pas un partage ftp",
				destDir, backupDir);
		}
		if(is_ftp_prefix(destDir) == false &&
			is_ftp_prefix(backupDir) == true) {
			fatal_error(L"la destination '%s' est locale mais le repertoire de backup '%s' est un partage ftp",
				destDir, backupDir);
		}
		if(is_ftp_prefix(destDir) == true &&
		   is_ftp_prefix(backupDir) == true) {
			int i = 0;
			for(i=sizeof(ftp_prefix)/sizeof(wchar_t);
				destDir[i]!=L'\0' && backupDir[i]!=L'\0' && destDir[i]!=L'/' && backupDir[i]!=L'/';
				i++) {
				if(destDir[i] != backupDir[i]) {
					fatal_error(L"la destination '%s' et le répertoire de backup '%s' ne sont pas sur le meme partage ftp",
						destDir, backupDir);
				}
			}
		}
	}

}

bool must_exclude_file(wchar_t * file) {
	bool ret = false;
	for(vector<wstring>::iterator it = exclude_files.begin();
		it != exclude_files.end();
		it++) {
		if(wcscmp(file, it->c_str())==0) {
			ret = TRUE;
			break;
		}
	}
	return ret;
}

bool must_exclude_dir(wchar_t * dir) {
	bool ret = false;
	for(vector<wstring>::iterator it = exclude_dirs.begin();
		it != exclude_dirs.end();
		it++) {
		if(wcscmp(dir, it->c_str())==0) {
			ret = TRUE;
			break;
		}
	}
	return ret;
}
} ;

vector<CFG_ITEM*> CFG;

wchar_t * trim(wchar_t * p) {
	for(;(*p)!=0;p++) {
		if((*p) != L' ' &&
			(*p) != L'\t' &&
		       	(*p) != L'\r' &&
		       	(*p) != L'\n') {
			break;
		}
	}

	if((*p)!=0) {
		int lg = 0;
		for(lg = (int) wcslen(p); lg!=0; lg--) {
			if(p[lg-1] != L' ' &&
				p[lg-1] != L'\t' &&
		       		p[lg-1] != L'\r' &&
		       		p[lg-1] != L'\n') {
				break;
			}
		}
		p[lg] = 0;
	}
	
	return p;
}

wchar_t * trim_and_convert_to_unicode(char * str) {
	size_t st = 0;
	for(st = strlen(str); st!=0; st--) {
		((wchar_t*)str)[st] = (wchar_t) str[st];
	}
	((wchar_t*)str)[0] = (wchar_t) str[0];

	return trim((wchar_t*)str);
}

/*wchar_t * trim_unicode(wchar_t * str) {
	size_t st = 0;
	for(st = 0; str[2*st+1]!=0; st++) {
		str[st] = str[2*st+1];
	}
	str[st]=0;

	return trim((wchar_t*)str);
}*/
wchar_t * trim_unicode_inverse(wchar_t * str) {
	size_t st = 0;
	//for(st = 0; str[2*st+1]!=0; st++) {
	//	str[st] = ((str[2*st+1]&0xFF)>>8) + (str[2*st+1]<<8);
	//}
	for(st = 0; str[st]!=0; st++) {
		str[st] = ((str[st]&0xFF)>>8) + (str[st]<<8);
	}
	str[st]=0;

	return trim((wchar_t*)str);
}

void read_cfg_param(CFG_ITEM * cfg_item, wchar_t * param, wchar_t * value, int line_number) {
	if(wcscmp(param, L"source") == 0) {
		if(cfg_item->sourceDir != NULL) {
			fatal_error(L"Parametre 'source' en doublon dans la section '%s' ligne %d", cfg_item->name, line_number);
		}
		cfg_item->sourceDir = _wcsdup(value);
	}
	else if(wcscmp(param, L"destination") == 0) {
		if(cfg_item->destDir != NULL) {
			fatal_error(L"Parametre 'destination' en doublon dans la section '%s' ligne %d", cfg_item->name, line_number);
		}
		cfg_item->destDir = _wcsdup(value);
	}
	else if(wcscmp(param, L"ftp_login") == 0) {
		if(cfg_item->ftp_userpwd != NULL) {
			fatal_error(L"Parametre 'ftp_login' en doublon dans la section '%s' ligne %d", cfg_item->name, line_number);
		}
		cfg_item->ftp_userpwd = _wcsdup(value);
	}
	else if(wcscmp(param, L"sauvegarde_fichiers_modifies") == 0) {
		if(cfg_item->backupDir != NULL) {
			fatal_error(L"Parametre 'sauvegarde_fichiers_modifies' en doublon dans la section '%s' ligne %d", cfg_item->name, line_number);
		}
		cfg_item->backupDir = _wcsdup(value);
	}
	else if(wcscmp(param, L"purge_sauvegarde_apres") == 0) {
		if(cfg_item->purgeIfOlder != -1) {
			fatal_error(L"Parametre 'purge_sauvegarde_apres' en doublon dans la section '%s' ligne %d", cfg_item->name, line_number);
		}
		cfg_item->purgeIfOlder = _wtoi(value);
	}
	else if(wcscmp(param, L"exclude_dir") == 0) {
		cfg_item->exclude_dirs.push_back(wstring(value));
	}
	else if(wcscmp(param, L"exclude_file") == 0) {
		cfg_item->exclude_files.push_back(wstring(value));
	}
	else {
		fatal_error(L"Parametre inconnu : '%s'", param);
	}

}

char * cfg_file_content = NULL;
size_t cfg_file_content_len = 0;
char * cfg_file_pointer = NULL;

void read_cfg_file_complete(wchar_t * file_name) {
	bool unicode_mode = false;
	bool unicode_mode_inverse = false;

	FILE * cfg_file = _wfopen(file_name, L"rb");
	if(cfg_file == NULL) {
		fatal_error(L"impossible d'ouvrir le fichier '%s'", file_name);
	}

	size_t content_alloc = 64*1024;
	cfg_file_content = (char*) malloc(content_alloc);
	memset(cfg_file_content, 0, content_alloc);

	cfg_file_content_len = fread(cfg_file_content, 1, content_alloc, cfg_file);
	if(cfg_file_content_len < 1) {
		fatal_error(L"fichier '%s' vide ou incomplet", file_name);
	}

	if(!feof(cfg_file)) {
		fatal_error(L"fichier de configuration '%s' trop grand (limite %lu octets)", file_name, content_alloc);
	}

	fclose(cfg_file);
	cfg_file = NULL;

	if(((wchar_t*)cfg_file_content)[0] == 0xFEFF) {
		unicode_mode = true;
	}
	else if(((wchar_t*)cfg_file_content)[0] == 0xFFFE) {
		unicode_mode_inverse = true;
	}


	if(unicode_mode_inverse == true) {
		// unicode mais pas le bon endian : il faut inverser les caracteres
		for(size_t i=0; i<cfg_file_content_len; i+=2) {
			//cfg_file_content[i] = (cfg_file_content[i]>>8) + (cfg_file_content[i+1]);
			char c = cfg_file_content[i];
			cfg_file_content[i] = cfg_file_content[i+1];
			cfg_file_content[i+1] = c;
		}
	}
	else if(unicode_mode == false) {
		// ascii simple : il faut doubler le nombre d'octets ...
		wchar_t * new_p = (wchar_t*) malloc((cfg_file_content_len+1)*sizeof(wchar_t));
		for(size_t i=0; i!=cfg_file_content_len; i++) {
			new_p[i] = (wchar_t) cfg_file_content[i];
		}
		((wchar_t*)new_p)[cfg_file_content_len] = L'\0';
		free(cfg_file_content);
		cfg_file_content = (char*) new_p;
		new_p = NULL;
		cfg_file_content_len *= 2;
	}
}

wchar_t * cfg_file_read_next_line() {
	if(cfg_file_pointer == NULL) {
		cfg_file_pointer = cfg_file_content;
		if(((wchar_t*)cfg_file_content)[0] == 0xFEFF ||
		   ((wchar_t*)cfg_file_content)[0] == 0xFFFE) {
			cfg_file_pointer += 2;
		}
	}

	if(cfg_file_pointer >= cfg_file_content+cfg_file_content_len) {
		return NULL;
	}

	wchar_t* ret = (wchar_t*) cfg_file_pointer;
	wchar_t * p  = (wchar_t*) cfg_file_pointer;

	for(;((char*)p)<cfg_file_content+cfg_file_content_len; p++) {
		if((p[0] == L'\r' && p[1] != L'\n') ||
		   (p[0] == L'\n' && p[1] != L'\r')) {
			p[0] = L'\0';
			cfg_file_pointer = (char*) (p+1);
			break;
		}
		else if((p[0] == L'\r' && p[1] == L'\n') ||
		        (p[0] == L'\n' && p[1] == L'\r')) {
			p[0] = L'\0';
			p[1] = L'\0';
			cfg_file_pointer = (char*) (p+2);
			break;
		}
	}

	return ret;
}

void read_cfg_file(wchar_t * argv0) {
	path ini_file_name;
	ini_file_name.init_with_exe_path(argv0);
	ini_file_name.add_trailing_backslash_if_necessary();
	ini_file_name.cat(L"jebsync.ini");
	//boolean unicode_mode = false;
	//boolean unicode_mode_inverse = false;

	log_msg(L"INI FILE : %s", ini_file_name.get());

	wchar_t * nontrim_line = NULL;
	//wchar_t buff[512+1];
	wchar_t curr_section[512+1];
	//memset(buff, 0, sizeof(buff));
	memset(curr_section, 0, sizeof(curr_section));
	CFG_ITEM * curr_cfg_item = NULL;



	//FILE * cfg_file = fopen("jebsync.ini", "r");
	/*FILE * cfg_file = _wfopen(ini_file_name.get(), L"r");
	if(cfg_file == NULL) {
		fatal_error(L"impossible d'ouvrir le fichier '%s'", ini_file_name.get());
	}

	*/
	int line_number = 0;
	/*if(fread(buff, 2, 1, cfg_file) < 1) {
		fatal_error(L"fichier '%s' vide ou incomplet", ini_file_name.get());
	}
	if(buff[0] == 0xFEFF) {
	//	log_msg(L"fichier ini : unicode");
		unicode_mode = true;
		//buff[0] = buff[1];
	}
	else if(buff[0] == 0xFFFE) {
		unicode_mode_inverse = true;
		//buff[0] = buff[1];
	}
	//else {
	//	log_msg(L"fichier ini : ascii");
	//}
	*/


	//while((unicode_mode||unicode_mode_inverse
	// 	? (void*) fgets(((char*)buff), sizeof(buff)/sizeof(wchar_t), cfg_file) 
	//	: (void*) fgets(((char*)buff)+(line_number==0?2:0), sizeof(buff)/sizeof(wchar_t)-(line_number==0?1:0), cfg_file)) 
		// on lit le meme nombre de caractere ascii ou unicode, meme si le buffer nous permettrait de lire deux fois plus de caracteres ascii
		// => comme ca pas de difference sur la taille max de la ligne entre les fichiers ascii et unicode ...
	read_cfg_file_complete(ini_file_name.get());

	while((nontrim_line = cfg_file_read_next_line())
		!= NULL) {
		/*if(unicode_mode) {
			if(((char*)buff)[1] == (char) 0x0a &&
			   ((char*)buff)[0] == (char) 0x00 &&
				buff[1] == L'\0') {
				continue;
			}
		}*/
		line_number++;

		wchar_t * line = trim(nontrim_line);
		/*NULL;
		if(unicode_mode) {
			line = trim(buff);
		}
		else if(unicode_mode_inverse) {
			line = trim_unicode_inverse(buff);
		}
		else {
			line = trim_and_convert_to_unicode((char*) buff);
		}*/

		size_t line_lg = wcslen(line);
		
		if((*line) == 0 ||
			(*line) == L'\'' ||
			(*line) == L'/' ||
			(*line) == L'#') {
			continue;
		}
		
		if(line[0] == L'[') {
			// lecture d'une section
			if(line[line_lg-1] != ']') {
				fatal_error(L"Erreur de syntaxe dans le fichier de configuration, ligne %d : caractère ']' manquant", line_number);
			}
			
			line[line_lg-1] = 0;
			line++;
			line = trim(line);

			wcscpy(curr_section, line);
			//printf("section : '%s'\n", curr_section);
			curr_cfg_item = new CFG_ITEM(curr_section);
			CFG.push_back(curr_cfg_item);
		}
		else {
			// lecture d'un parametre
			wchar_t * p = NULL;
			for(p=line; *p!=0; p++) {
				if(*p==L'=') {
					break;
				}
			}
			if(*p==0) {
				fatal_error(L"Erreur de syntaxe dans le fichier de configuration, ligne %d : caractère '=' manquant pour définir un parametre", line_number);
			}

			*p=0;
			p++;

			wchar_t * param = trim(line);
			wchar_t * value = trim(p);
			//printf("parametre '%s'='%s'\n", param, value);

			if(curr_cfg_item == NULL) {
				fatal_error(L"section manquante dans le fichier de configuration");
			}

			read_cfg_param(curr_cfg_item, param, value, line_number);
		}

	}

	//fclose(cfg_file);
	free(cfg_file_content);
	cfg_file_content = NULL;

	for(vector<CFG_ITEM*>::iterator it=CFG.begin(); it!=CFG.end(); it++) {
		(*it)->check();
	}
}


//static bool testOnly = false;

///////////////////////////////////////////////////////////////////////////////
// main
void list_dir_callback(wchar_t * source_dir, /*wchar_t * destination_dir, wchar_t * backup_dir, int purge_if_older,*/ wchar_t * subdir, wchar_t * file_name, bool is_dir, time_t source_last_modified_date, void * context, bool *pf_dir_excluded) {
	*pf_dir_excluded = false;
	CFG_ITEM * cfg_item = (CFG_ITEM*) context;

	wchar_t * destination_dir = cfg_item->destDir;
	wchar_t * backup_dir = cfg_item->backupDir;
	int purge_if_older = cfg_item->purgeIfOlder;

	if(is_dir) {
		if(cfg_item->must_exclude_dir(file_name) == true) {
			char sep = is_ftp_prefix(source_dir) ? L'/' : L'\\';
			if(subdir == NULL) {
				log_msg(L"exclusion du répertoire '%s%c%s'",
					source_dir, sep, file_name);
			} 
			else {
				log_msg(L"exclusion du répertoire '%s%c%s%c%s'",
					source_dir, sep, subdir, sep, file_name);
			}
			*pf_dir_excluded = true;
			return;
		}

		path dest_dir_name(destination_dir, (subdir!=NULL?wcslen(subdir)+1:0)+wcslen(file_name)+1);
		if(subdir!=NULL) {
			dest_dir_name.add_trailing_backslash_if_necessary();
			dest_dir_name.cat(subdir);
		}
		dest_dir_name.add_trailing_backslash_if_necessary();
		dest_dir_name.cat(file_name);

		if(!check_dir(dest_dir_name.get(), destination_dir)) {
			log_msg(L"+ %s'", dest_dir_name.get());
			if(!globalOptionTestOnly) {
				if(create_dir(dest_dir_name.get()) != 0) {
					// nothing : si la creation du répertoire ne marche pas, on ne fait rien
					// si des fichiers du repertoires doivent etre crees, on aura des erreurs
					// pour chaque fichier. c'est le comportement souhaite ...
				}
			}
		}

	}
	else {

		if(cfg_item->must_exclude_file(file_name) == true) {
			char sep = is_ftp_prefix(source_dir) ? L'/' : L'\\';
			if(subdir == NULL) {
				log_msg(L"exclusion du fichier '%s%c%s'",
					source_dir, sep, file_name);
			} 
			else {
				log_msg(L"exclusion du fichier '%s%c%s%c%s'",
					source_dir, sep, subdir, sep, file_name);
			}
			*pf_dir_excluded = true;
			return;
		}
		//printf("dbg callback src='%s' dest='%s' sub='%s' file_name='%s' is_dir=%d last_modified_date=%lu\n",
			//source_dir, destination_dir, subdir, file_name, is_dir, last_modified_date);

		path dest_file_name(destination_dir, (subdir==NULL?0:wcslen(subdir)+1)+wcslen(file_name)+1);
		if(subdir!=NULL) {
			dest_file_name.add_trailing_backslash_if_necessary();
			dest_file_name.cat(subdir);
		}
		dest_file_name.add_trailing_backslash_if_necessary();
		dest_file_name.cat(file_name);

		path source_file_name(source_dir, (subdir==NULL?0:wcslen(subdir)+1)+wcslen(file_name)+1);
		if(subdir!=NULL) {
			source_file_name.add_trailing_backslash_if_necessary();
			source_file_name.cat(subdir);
		}
		source_file_name.add_trailing_backslash_if_necessary();
		source_file_name.cat(file_name);

		time_t dest_last_write_time = get_file_time(dest_file_name.get());
		if(dest_last_write_time == (time_t) -1) {
			// nothing
		}
		else if(dest_last_write_time == 0) {
			log_msg(L"+ %s", dest_file_name);
			if(!globalOptionTestOnly) {
				if(copy_file(source_file_name.get(), dest_file_name.get())) {
					// nothing : on ne fait rien : l'erreur est deja loguee
					// et il faut continuer a traiter les autres fichier malgre l'erreur
				}
			}
		}
		else if(dest_last_write_time < source_last_modified_date) {
			/*time_t tt = source_last_modified_date-dest_last_write_time;
			int jours = (int) (tt/(24*60*60));
			tt -= jours*(24*60*60);
			int heures = (int) (tt/(60*60));
			tt -= heures*(60*60);
			int minutes = (int) (tt/60);
			tt -= minutes*60;
			int secondes = (int) tt;*/
			
			if(file_identifical(source_file_name.get(), dest_file_name.get())) {
				struct tm * pt;
				pt = localtime(&source_last_modified_date);

				log_msg(L"t[%4.4d%2.2d%2.2d-%2.2d%2.2d%2.2d] %s", 
						pt->tm_year+1900, pt->tm_mon+1, pt->tm_mday,
						pt->tm_hour, pt->tm_min, pt->tm_sec,
						dest_file_name);
				if(!globalOptionTestOnly) {
					set_file_time(dest_file_name.get(), source_last_modified_date);
				}
				
			}
			else {
				if(backup_dir == NULL) {
					log_msg(L"X %s", dest_file_name);
					if(!globalOptionTestOnly) {
						if(delete_file(dest_file_name.get()) != 0) {
							// nothing : on essaie de continuer quand meme, au cas ou
							// normalement on doit avoir un deuxieme message d'erreur au moment de la copie
							// qui ne peut pas ecraser des fichiers existant
						}
					}
				}
				else {
					/*time_t current_tt = 0;
					struct tm current_tm;
					memset(&current_tm, sizeof(current_tm), 0);
					_time64(&current_tt);
					_localtime64_s(&current_tm, &current_tt);*/

					path backup_file_name(backup_dir, (subdir==NULL?0:wcslen(subdir)+1) + wcslen(file_name)+1+7+15);
					if(subdir != NULL) {
						backup_file_name.add_trailing_backslash_if_necessary();
						backup_file_name.cat(subdir);
					}
					backup_file_name.add_trailing_backslash_if_necessary();
					backup_file_name.cat(file_name);
					backup_file_name.cat(L".JSYNC-");
					//the timestamp used is the last modified datetime of the file
					//backup_file_name.cat_timestamp(reference_time);
					backup_file_name.cat_timestamp(source_last_modified_date);

					log_msg(L"B %s", backup_file_name);
					if(!globalOptionTestOnly) {
						if(move_file(dest_file_name.get(), backup_file_name.get(), true, backup_dir)) {
							// nothing : si on a une erreur on continue quand meme, au cas ou
							// on aura donc certainement une seconde erreur lors de la copie qui ne peut pas
							// ecraser
						}
					}

					// au passage on regarde s'il ne faut pas purger d'autres sauvegarde
					// les fichiers ne sont pas purges tout de suite mais a la fin
					// du traitement de la section
					if(purge_if_older > 0) {
						check_backup_for_purge(backup_dir, subdir, file_name, purge_if_older);
					}
				}
				log_msg(L"+ %s", dest_file_name.get());
				if(!globalOptionTestOnly) {
					if(copy_file(source_file_name.get(), dest_file_name.get())) {
						// nothing : on ne fait rien : l'erreur est deja loguee
						// et il faut continuer a traiter les autres fichier malgre l'erreur
					}
				}
			}
		}
		else if(dest_last_write_time > source_last_modified_date) {
			simple_error(L"destination plus récente que la source : '%s'", source_file_name.get());
		}
	}
}

void usage(void) {
	printf("jebsync [-t] [-cv]\n");
	printf(" -t  : test uniquement. pas de copie de fichier ni de suppression, mais affiche les actions a effectuer\n");
	printf(" -cv : curl_verbose\n");
	printf("\n");
}

int wmain(int argc, wchar_t * argv[]) {
	if(argc>3) {
		usage();
		exit(-1);
	}
	for(int i=1; i<argc; i++) {
		if(wcscmp(argv[i], L"-t") == 0 ||
			wcscmp(argv[i], L"-T") == 0) {
			globalOptionTestOnly = true;
		}
		else if(_wcsicmp(argv[i], L"-cv") == 0) {
			globalOptionCurlVerbose = true;
		}
		else {
			wprintf(L"option inconnue : %s\n", argv[i]);
			usage();
			exit(-1);
		}

	}

	reference_time = time(NULL);
	log_file_init(argv[0]);
	log_msg(L"JEBSYNC VERSION 0.0.4");
	log_msg(L"CVS ID $Id: main.cpp,v 1.17 2009/04/12 20:06:10 jeb Exp $");
	if(globalOptionTestOnly) {
		log_msg(L"option test activee : aucune ecriture");
	}
	drives_names_init();
	read_cfg_file(argv[0]);


	for(vector<CFG_ITEM*>::iterator it=CFG.begin(); it!=CFG.end(); it++) {
		reset_error_count();
		log_msg(L"====================");
		log_msg(L"[%s]", (*it)->name);
	    log_msg(L"Repertoire source       '%s'", (*it)->sourceDir);
	    log_msg(L"Repertoire destination  '%s'", (*it)->destDir);
	    log_msg(L"Repertoire backup       '%s'", (*it)->backupDir==NULL?L"":(*it)->backupDir);
		log_msg(L"Purge des backups apres %d jours", (*it)->purgeIfOlder);
		for(vector<wstring>::iterator it_exc = (*it)->exclude_files.begin();
			it_exc != (*it)->exclude_files.end();
			it_exc++) {
			log_msg(L"Exclusion du fichier    '%s'", (*it_exc).c_str());
		}
		for(vector<wstring>::iterator it_exc = (*it)->exclude_dirs.begin();
			it_exc != (*it)->exclude_dirs.end();
			it_exc++) {
			log_msg(L"Exclusion du répertoire '%s'", (*it_exc).c_str());
		}
		log_msg(L"====================");
		if(is_ftp_prefix((*it)->destDir)) {
			ftp_init_connection((*it)->ftp_userpwd, globalOptionCurlVerbose);
		}
		//list_dir((*it)->sourceDir, (*it)->destDir, (*it)->backupDir, (*it)->purgeIfOlder, NULL, true, list_dir_callback, *it);
		list_dir((*it)->sourceDir, NULL, true, list_dir_callback, *it);
		if(is_ftp_prefix((*it)->destDir)) {
			ftp_close_connection();
		}
		delete_files_to_purge();
		files_to_purge_empty();
		log_msg(L"");
		log_msg(L"Nombre d'erreurs rencontrées : %d", get_error_count());
		log_msg(L"");
	}

	log_file_close();

	return 0;
}

