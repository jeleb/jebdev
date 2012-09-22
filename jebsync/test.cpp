#include <windows.h>
#include <string>
#include <stdio.h>
#include "lru_cache.cpp"

#define test_assert(x) \
 if(!(x)) { \
  wprintf(L"ERROR in %S:%d asserting : %s\n", __FILE__, __LINE__, L#x); \
  exit(-1); \
 } 

#define test_assert_lru_cached_get(x) \
 { \
  create_flag = false; \
  delete_flag = false; \
  last_deleted = L""; \
  my_key the_my_key(x); \
  ce * myce = the_cache.cached_get(&the_my_key); \
  test_assert(myce != NULL); \
  test_assert(wcscmp(myce->get_wstr(), x.get_wstr())==0); \
 }



static bool create_flag = false;
static bool delete_flag = false;
static wstring last_deleted;



class my_key : public lru_cache_key {
	wstring key;

public:
	my_key(const wchar_t * the_key) {
		key = the_key;
	}

	unsigned char * get_key_buff(void) {
		return (unsigned char*) key.c_str();
	}
	size_t get_key_buff_length(void) {
		return key.length() * sizeof(wchar_t);
	}

	wchar_t * get_wstr(void) {
		return (wchar_t*) key.c_str();
	}
};

class ce {
 wstring wstr;

public:
 ce(my_key * the_key) {
  wstr = the_key->get_wstr();

  //_wcsupr_s((wchar_t*) wstr.c_str(), wstr.length()+1);

  //wprintf(L"new %s\n", wstr.c_str());
  create_flag = true;
 }
 virtual ~ce(void) {
  //wprintf(L"del %s\n", wstr.c_str());
  last_deleted = wstr;
  delete_flag = true;
 }

 const wchar_t * get_wstr(void) {
  return wstr.c_str();
 }

};

int wmain(void) {
 lru_cache<ce, my_key> the_cache(5);

 wprintf(L"test du cache MRU\n");
 
 test_assert_lru_cached_get(my_key(L"tutu_1"));
 test_assert(create_flag == true);
 test_assert(delete_flag == false);

 test_assert_lru_cached_get(my_key(L"tutu_2"));
 test_assert(create_flag == true);
 test_assert(delete_flag == false);

 test_assert_lru_cached_get(my_key(L"tutu_2"));
 test_assert(create_flag == false);
 test_assert(delete_flag == false);

 
 test_assert_lru_cached_get(my_key(L"tutu_3"));
 test_assert(create_flag == true);
 test_assert(delete_flag == false);
 
 test_assert_lru_cached_get(my_key(L"tutu_4"));
 test_assert(create_flag == true);
 test_assert(delete_flag == false);
 
 test_assert_lru_cached_get(my_key(L"tutu_5"));
 test_assert(create_flag == true);
 test_assert(delete_flag == false);
 
 test_assert_lru_cached_get(my_key(L"tutu_1"));
 test_assert(create_flag == false);
 test_assert(delete_flag == false);

 test_assert_lru_cached_get(my_key(L"tutu_6"));
 test_assert(create_flag == true);
 test_assert(delete_flag == true);
 test_assert(last_deleted == L"tutu_2");
 
 test_assert_lru_cached_get(my_key(L"tutu_1"));
 test_assert(create_flag == false);
 test_assert(delete_flag == false);

 test_assert_lru_cached_get(my_key(L"tutu_2"));
 test_assert(create_flag == true);
 test_assert(delete_flag == true);
 test_assert(last_deleted == L"tutu_3");


 test_assert_lru_cached_get(my_key(L"tutu_3"));
 test_assert(create_flag == true);
 test_assert(delete_flag == true);
 test_assert(last_deleted == L"tutu_4");


 test_assert_lru_cached_get(my_key(L"tutu_7"));
 test_assert(create_flag == true);
 test_assert(delete_flag == true);
 test_assert(last_deleted == L"tutu_5");

 

 wprintf(L"test OK\n");
 return 0;
}

