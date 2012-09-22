using namespace std;

class buffer {
	unsigned char * buff;
	size_t length;
	size_t alloc;

public:
	buffer(unsigned char * new_buff, size_t new_length) {
		set(new_buff, new_length);
	}
	buffer(void) {
		length = 0;
		alloc = 0;
		buff = NULL;
	}
	~buffer() {
		if(buff != NULL) {
			delete [] buff;
		}
	}

	void set(unsigned char * new_buff, size_t new_length) {
		length = new_length;
		alloc = length;
		buff = new unsigned char[alloc];
		memcpy(buff, new_buff, new_length);
	}

	void clear() {
		if(buff != NULL) {
			delete [] buff;
		}
		buff = NULL;
		length = 0;
		alloc = 0;
	}


	unsigned char * get_buffer(void) {
		return buff;
	}
	size_t get_buffer_length(void) {
		return length;
	}

	bool equals(unsigned char * b_buff, size_t b_length) {
		if(length != b_length) {
			return false;
		}

		if(memcmp(buff, b_buff, length)!=0) {
			return false;
		}

		return true;
	}

	bool equals(buffer & b) {
		return equals(b.buff, b.length);
	}

};


static unsigned long crc32_hash_ulTable[256]; // CRC lookup table array.
static bool crc32_hash_table_init = false;


unsigned long crc32_hash_reflect(unsigned long ulReflect, const char cChar)
{
    unsigned long ulValue = 0;

    // Swap bit 0 for bit 7, bit 1 For bit 6, etc....
    for(int iPos = 1; iPos < (cChar + 1); iPos++)
    {
        if(ulReflect & 1)
        {
            ulValue |= (1 << (cChar - iPos));
        }
        ulReflect >>= 1;
    }

    return ulValue;
}

void crc32_hash_partial(unsigned long ulTable[256], unsigned long *ulCRC, const unsigned char *sData, unsigned long ulDataLength)
{
    while(ulDataLength--)
    {
        //If your compiler complains about the following line, try changing each
        //    occurrence of *ulCRC with "((unsigned long)*ulCRC)" or "*(unsigned long *)ulCRC".

         *ulCRC =
            ((*ulCRC) >> 8) ^ ulTable[((*ulCRC) & 0xFF) ^ (*sData)];
		 sData++;
    }
}


unsigned long crc32_hash(const unsigned char * data, size_t data_len) {

  // initialize
  if(crc32_hash_table_init == false) {
    //0x04C11DB7 is the official polynomial used by PKZip, WinZip and Ethernet.
    unsigned long ulPolynomial = 0x04C11DB7;

    //memset(&this->ulTable, 0, sizeof(this->ulTable));

    // 256 values representing ASCII character codes.
    for(int iCodes = 0; iCodes <= 0xFF; iCodes++)
    {
        crc32_hash_ulTable[iCodes] = crc32_hash_reflect(iCodes, 8) << 24;

        for(int iPos = 0; iPos < 8; iPos++)
        {
            crc32_hash_ulTable[iCodes] = (crc32_hash_ulTable[iCodes] << 1)
                ^ ((crc32_hash_ulTable[iCodes] & (1 << 31)) ? ulPolynomial : 0);
        }

        crc32_hash_ulTable[iCodes] = crc32_hash_reflect(crc32_hash_ulTable[iCodes], 32);
    }
	crc32_hash_table_init = true;
  }
  
  unsigned long ulCRC = 0xffffffff; //Initilaize the CRC.
  crc32_hash_partial(crc32_hash_ulTable, &ulCRC, data, data_len);
  return(ulCRC ^ 0xffffffff); //Finalize the CRC and return.
}


class lru_cache_key {
public :
	virtual ~lru_cache_key(void) {
	}

	virtual unsigned char * get_key_buff(void) = 0;
	virtual size_t get_key_buff_length(void) = 0;
};


class lru_cache_hashmap_entry;

class lru_cache_list_entry {
public:
 lru_cache_list_entry * prev;
 lru_cache_list_entry * next;
 lru_cache_hashmap_entry * hashmap_entry;
};

class lru_cache_hashmap_entry {
public:
 int state;
 //wstring key;
 buffer key;
 lru_cache_list_entry * list_entry;
 void * value;
};

#define HASHMAP_FACTOR              1.5
#define HASHMAP_STATE_EMPTY           0
#define HASHMAP_STATE_EMPTY_CONTINUED 1
#define HASHMAP_STATE_FULL            2
#define HASHMAP_STATE_FULL_CONTINUED  3

template <class t, class t_key> class lru_cache {
 size_t max_size;
 size_t hashmap_size;

 lru_cache_list_entry * list_alloc;
 lru_cache_list_entry * first_free;
 lru_cache_list_entry * last_free;
 lru_cache_list_entry * first;
 lru_cache_list_entry * last;
 lru_cache_hashmap_entry * hashmap;

public :
 lru_cache(size_t size) {
  max_size = size;
  hashmap_size = (size_t) (max_size*HASHMAP_FACTOR);
  list_alloc = new lru_cache_list_entry[max_size];
  hashmap = new lru_cache_hashmap_entry[hashmap_size];
  first = NULL;
  last = NULL;
  first_free = NULL;
  last_free = NULL;

  for(lru_cache_hashmap_entry * p=hashmap; p!=hashmap+hashmap_size; p++) {
   p->state      = HASHMAP_STATE_EMPTY ;
   p->list_entry = NULL;
   p->value      = NULL;
  }

  for(lru_cache_list_entry * p=list_alloc; p!=list_alloc+max_size; p++) {
   p->prev = NULL;
   p->next = first_free;
   if(first_free == NULL) {
    last_free = p;
   }
   else {
    first_free->prev = p;
   }
   first_free = p;

   p->hashmap_entry = NULL;
  }
 }

 ~lru_cache() {
  delete [] list_alloc;
  delete [] hashmap;
 }

protected:

 /*size_t hash_position(wstring & key) {
  unsigned long hash = crc32_hash((const unsigned char*) (key.c_str()), key.length()*sizeof(wchar_t));
  size_t st = (size_t) ( ((unsigned long) (hash % ((unsigned long) hashmap_size))) );
  return st;
 }*/
 size_t hash_position(char * buff, size_t buff_length) {
  unsigned long hash = crc32_hash((const unsigned char*)buff, buff_length);
  size_t st = (size_t) ( ((unsigned long) (hash % ((unsigned long) hashmap_size))) );
  return st;
 }

 void add(lru_cache_key * key, void * value) {
  // if no space available, behaviour unspecified
  // (NULL-pointer reference)

  size_t hash_pos = hash_position((char*) key->get_key_buff(), key->get_key_buff_length());
  lru_cache_hashmap_entry * p_hash = hashmap+hash_pos;
  lru_cache_hashmap_entry * p_hash_end = hashmap+hashmap_size;

  while(p_hash->state != HASHMAP_STATE_EMPTY &&
   p_hash->state != HASHMAP_STATE_EMPTY_CONTINUED) {
   // bon a savoir : la table de hash ne peut pas etre pleine
   
   if(p_hash->state == HASHMAP_STATE_FULL) {
    p_hash->state = HASHMAP_STATE_FULL_CONTINUED;
   }

   p_hash ++;
   if(p_hash == p_hash_end) {
    p_hash = hashmap;
   }
  }

  lru_cache_list_entry * p_list = first_free;

  p_hash->key.set(key->get_key_buff(), key->get_key_buff_length());
  p_hash->value = value;
  p_hash->state = HASHMAP_STATE_FULL;
  p_hash->list_entry = p_list;
  p_list->hashmap_entry = p_hash;

  first_free = p_list->next;
  if(first_free == NULL) {
   last_free = NULL;
  }
  else {
   first_free->prev = NULL;
  } 
  p_list->next = first;
  p_list->prev = NULL;
  if(first == NULL)  {
   last = p_list;
  }
  else {
   first->prev = p_list;
  }
  first = p_list;
 }

 void * get(lru_cache_key * key) {
  size_t hash_pos = hash_position((char*) key->get_key_buff(), key->get_key_buff_length());
  lru_cache_hashmap_entry * p_hash = hashmap+hash_pos;
  lru_cache_hashmap_entry * p_hash_end = hashmap+hashmap_size;
  void * p_found = NULL;

  while(p_hash->state != HASHMAP_STATE_EMPTY) {

   if( (p_hash->state == HASHMAP_STATE_FULL ||
        p_hash->state == HASHMAP_STATE_FULL_CONTINUED)
		&& p_hash->key.equals(key->get_key_buff(), key->get_key_buff_length())) {
    p_found = p_hash->value;
    break;
   }

   if(p_hash->state == HASHMAP_STATE_FULL) {
	   break;
   }

   p_hash ++;
   if(p_hash == p_hash_end) {
    p_hash = hashmap;
   }

  };

  if(p_found != NULL) {
   lru_cache_list_entry * p_list = p_hash->list_entry;
   // s'il s'agit deja de la premiere entree de la liste
   // il n'y a rien a faire
   if(p_list->prev != NULL) {
    p_list->prev->next = p_list->next;
    if(p_list->next == NULL) {
     last = p_list->prev;
     last->next = NULL;
    }
    else {
     p_list->next->prev = p_list->prev;
    }
    p_list->prev = NULL;
    p_list->next = first;
    first->prev  =  p_list;
    
   }
   
  }
  return p_found;
 }

 void free_lru(void) {
  lru_cache_list_entry * p_list = last;
  if(p_list != NULL) {
   last = p_list->prev;
   if(last == NULL) {
    first = NULL;
   }
   else {
    last->next = NULL;
   }
   p_list->prev = NULL;
   p_list->next = NULL;

   lru_cache_hashmap_entry * p_hashmap = p_list->hashmap_entry;
   p_list->hashmap_entry = NULL;
   void * delete_value = p_hashmap->value;
   p_hashmap->key.clear();
   p_hashmap->list_entry = NULL;
   switch(p_hashmap->state) {
   case HASHMAP_STATE_FULL:
    {
     p_hashmap->state = HASHMAP_STATE_EMPTY;
     lru_cache_hashmap_entry  * p_hashmap_state_check = p_hashmap;
     for(;;) {
      if(p_hashmap_state_check == hashmap) {
       p_hashmap_state_check += hashmap_size;
      }
      p_hashmap_state_check--;
 
      if(p_hashmap_state_check->state == HASHMAP_STATE_EMPTY_CONTINUED) {
       p_hashmap_state_check->state = HASHMAP_STATE_EMPTY;
      }
      else {
       break;
      }
     }
    }

    break;

   case HASHMAP_STATE_FULL_CONTINUED :
    p_hashmap->state = HASHMAP_STATE_EMPTY_CONTINUED;
    break;

   default:
    // others cases are anormal but no error
    // wtf, this has to work and that's all
    break;
   }

   p_list->next = first_free;
   p_list->prev = NULL;
   if(first_free == NULL) {
    last_free = p_list;
   }
   else {
    first_free->prev = p_list;
   }
   first_free = p_list;

   if(delete_value != NULL) {
    delete ((t*)delete_value);
   }
  }
 }

public:
 t* cached_get(lru_cache_key * key) {
  t * ret = NULL;

  ret = (t*) get(key);
  if(ret == NULL) {
   // at this point the constructor of <t> can use the lru_cache class to request a key
   // at the condition the it doesn't request the value for the same key
   // which shoud not have any meening at all, anyway ...

   t_key * k = (t_key*) (key);
   ret = new t(k);

   if(first_free == NULL) {
    free_lru();
   }
   add(key, ret);
  }

  return ret;
 }
 

};

