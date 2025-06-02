use image::ImageFormat;
use std::ffi::CStr;
use std::os::raw::{c_char, c_int};

// Exporting the function with C linkage
#[unsafe(no_mangle)]
pub extern "C" fn convert_image(
    input_path: *const c_char,
    output_path: *const c_char,
    format_code: c_int,
) -> c_int {
    if input_path.is_null() || output_path.is_null() {
        return -1;
    }

    let input_cstr = unsafe { CStr::from_ptr(input_path) };
    let output_cstr = unsafe { CStr::from_ptr(output_path) };

    let input_str = match input_cstr.to_str() {
        Ok(s) => s,
        Err(_) => return -2,
    };

    let output_str = match output_cstr.to_str() {
        Ok(s) => s,
        Err(_) => return -3,
    };

    let format = match format_code {
        0 => ImageFormat::Jpeg,
        1 => ImageFormat::Png,
        2 => ImageFormat::WebP,
        _ => return -5,
    };

    let image = match image::open(input_str) {
        Ok(i) => i,
        Err(_) => return -6,
    };

    match image.save_with_format(output_str, format) {
        Ok(_) => 0,
        Err(_) => -7,
    }
}
