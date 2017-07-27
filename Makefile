CC=gcc
# -ggdb3 is for valgrind, -g option is for gdb debugger; remove in production
CFLAGS=-g -pedantic -std=c99 -Wall -Wextra -ggdb3 -std=gnu11
LFLAGS=-lcurl
MYSQL_CONFIG=`mysql_config --cflags --libs`

all: relative_value

relative_value: relative_value.o libc/cJSON/cJSON.o
	$(CC) $(CFLAGS) relative_value.o libc/cJSON/cJSON.o $(MYSQL_CONFIG) -o relative_value $(LFLAGS)

relative_value.o: relative_value.c
	$(CC) $(CFLAGS) -c relative_value.c $(MYSQL_CONFIG) $(LFLAGS)

clean:
	rm relative_value *.o