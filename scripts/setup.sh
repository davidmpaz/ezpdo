#!/bin/sh
# 
# This script creates and sets permissions on output directories 
# for EZPDO testing and logging
# 
# @author Oak Nauhygon <ezpdo4php@gmail.com>
# @version $Revision: 181 $ $Date: 2005-04-17 12:44:12 -0400 (Sun, 17 Apr 2005) $
# @package ezpdo
# @subpackage script

# directories to setup
output_dir="output";
log_dir="log";
compiled_dir="compiled";

# permission to be set
perms="ug+rw";

# Set up output dir recursively
# @param path to setup
dir_setup() {
    
    # check if path is specified
    path="$1";
    if [ -z "$path" ]; then
	# if not work on the current dir
	path='.';
    fi
    
    # cd to path
    cd $path;
    
    # get pwd
    pwd=`pwd`;
    
    # Go through each subdir under the current dir
    for i in *; do
        
	# explore only non-CVS directories
	if [ -d "$i" ] && [ "$i" != "CVS" ]; then
            
	    # Change permission on the current test output dir
            if [ "$i" = "$output_dir" -o "$i" = "$log_dir" -o "$i" = "$compiled_dir" ]; then
		chmod -R $perms "$i";
		echo "chmod -R $perms $pwd/$i";
            fi
	    
	    # Set up output dir recursively
	    dir_setup $i
        
	fi
    
    done
    
    # done with the path
    cd ..;
    
    return 0;
}

# call the recursive function 
dir_setup $1;

if [ 0 -eq $? ]; then
    echo "Output directories are set up and ready to use.";
else
    echo "Something has gone wrong. Please fix this script."; 
fi

exit 0;

