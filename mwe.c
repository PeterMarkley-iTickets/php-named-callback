#include <stdio.h>

void greet(char *greeting) {
	fprintf(stdout, "%s\n", greeting);
}

void execute_func(char *arg, void (*func)(char *)) {
	func(arg);
}

int main(int argc, char **argv) {
	execute_func("hello, world", greet);
	return 0;
}
