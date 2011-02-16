#!/bin/sh
# 
# This script cleans up output from tests and examples
# 
# @author Oak Nauhygon <ezpdo4php@gmail.com>
# @version $Revision: 181 $ $Date: 2005-04-17 12:44:12 -0400 (Sun, 17 Apr 2005) $
# @package ezpdo
# @subpackage script

# directories to cleanup
output_dir="output";
compiled_dir="compiled";

# Set up output dir recursively
# @param path 
dir_cleanup() {
    
    # check if path is specified
    path="$1";
    if [ -z "$path" ]; then
	# if not work on the current dir
	path='.';
    fi
    
    # cd to path
    cd $path;
    
    # Go through each subdir under the current dir
    for i in *; do
	
	if [ -d "$i" ]; then
            
	    # Remove example/test results
            if [ "$i" = "$output_dir" -o "$i" = "$log_dir" -o "$i" = "$compiled_dir" ]; then
		\rm -rf "$i"/*;
            fi
	    
	    # cleanup output dir recursively
	    dir_cleanup $i;
        
	fi
    
    done
    
    # done with this dir
    cd ..;
    
    return 0;
}

# call the recursive function 
dir_cleanup $1;

if [ 0 -eq $? ]; then
    echo "Cleaning up output/compiled directories done.";
else
    echo "Something has gone wrong. Please fix this script."; 
fi

exit 0;

