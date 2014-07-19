#!/bin/sh

#
#  +------------------------------------------------------------------------+
#  | Caribou                                             		           |
#  +------------------------------------------------------------------------+
#  | Copyright (c) 2014 natronite     					                   |
#  +------------------------------------------------------------------------+
#  | This source file is subject to the New BSD License that is bundled     |
#  | with this package in the file LICENSE                                  |
#  |                                                                        |
#  | If you did not receive a copy of the license and are unable to         |
#  | obtain it through the world-wide-web, please send an email             |
#  | to natronite@gmail.com so I can send you a copy immediately.           |
#  +------------------------------------------------------------------------+
#  | Authors: Nate Maegli <natronite@gmail.com>                             |
#  +------------------------------------------------------------------------+
#

dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd -P)"
if [ -L $0 ]; then
    file="$( readlink "${BASH_SOURCE[0]}" )"
    dir="$( cd "$( dirname "${file}" )" && pwd -P)"
fi
php "$dir/caribou.php" $*