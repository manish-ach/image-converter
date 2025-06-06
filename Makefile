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

.PHONY: all build copy prepare clean binary rust-lib

all: binary

rust-lib: prepare build copy

prepare:
	@mkdir -p lib
	@mkdir -p output

build:
	@echo "Building Rust library..."
	@cd rustffi && cargo build --release

copy:
	@echo "Copying library..."
	@cp rustffi/target/release/librust_image_converter.$(EXT) lib/

binary: rust-lib
	@echo "Creating binary..."
	@php -d phar.readonly=0 build-phar.php
	@echo "Making executables..."
	@chmod +x app.phar
	@chmod +x image-converter

clean:
	@cd rustffi && cargo clean
	@rm -rf lib/* output/*
	@rm -f app.phar image-converter

help:
	@echo "Available targets:"
	@echo "  all      - Build Rust library and create binary (default)"
	@echo "  build    - Build Rust library only"
	@echo "  copy     - Copy library to lib/ directory"
	@echo "  binary   - Create phar binary and symlink with executable permissions"
	@echo "  clean    - Clean all build artifacts"
	@echo "  help     - Show this help message"
