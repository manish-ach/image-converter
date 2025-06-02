ifeq ($(OS), Windows_NT)
	EXT := dll
else
	UNAME_S := $(shell uname -s)
	ifeq ($(UNAME_S),Linux)
		EXT := so
	endif
	ifeq ($(UNAME_S),Darwin)
		EXT := dylib
	endif
endif

.PHONY: all build copy prepare clean

all: prepare build copy

prepare:
	@mkdir -p lib
	@mkdir -p output

build:
	@cd rustffi && cargo build --release

copy:
	@cp rustffi/target/release/librust_image_converter.$(EXT) lib/

clean:
	@cd rustffi && cargo clean
	@rm -rf lib/* output/*
