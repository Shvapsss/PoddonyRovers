/**
 * JCH Optimize - Plugin to aggregate and minify external resources for
 * optmized downloads
 * @author Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2010 Samuel Marshall
 * @license GNU/GPLv3, See LICENSE file
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

var timer = null;
var done = false, current = 0, optimize = 0, total = 0;
            
function jchOptimizeImages(page){
        li = jQuery("#file-tree-container ul.jqueryFileTree").find("li.expanded").last();
        
        if(jQuery("#jform_params_kraken_api_key").val().length == 0)
        {
                alert("Please enter your API key");
                return false;
        }
        
        if(jQuery("#jform_params_kraken_api_secret").val().length == 0)
        {
                alert("Please enter your API secret");
                return false;
        }
        
        if(li.length>0){
                dir = li.find("a").attr("rel");
                        
                jQuery("#optimize-images-container")
                        .html('<div id="progressbar"></div>\
                         <div><ul id="optimize-log"><li>Optimizing images. Please wait...</li></ul></div>');
                jQuery("#progressbar").progressbar({value:0 });  
                        
                updateStatus(page, dir);
        }else{
                alert(message);
        }
};
                        
function updateStatus(page, dir){
                        
        var timestamp = getTimeStamp();

        var xhr = jQuery.ajax({
                dataType: "json",
                url: ajax_url + '&_=' + timestamp,
                data: {"dir":dir,"current":current,"optimize":optimize},
                success: function(data){ 

                        var pbvalue = 0;
                        total = data.total; 
                        current = data.current;  
                        optimize = data.optimize;

                        pbvalue = Math.floor((current / total) * 100);  

                        if(pbvalue>0){  
                                jQuery("#progressbar").progressbar({  
                                        value:pbvalue  
                                });  
                        
                                jQuery("ul#optimize-log").append('<li>' + data.message + '</li>');
                        }

                        if(total == current)
                        {
                                done = true;
                                jQuery("ul#optimize-log").append('<li>Adding logs to ' + data.log_path + '/plg_jch_optimize.logs.php...</li>');
                                setTimeout(function(){jQuery("ul#optimize-log").append('<li>Done!</li>');}, 1000);
                                window.location.href = page + "&dir=" + encodeURIComponent(dir) + "&cnt=" + optimize;
                        }
                        else
                        {
                                updateStatus(page,dir);
                        }
                },
                fail: function(jqXHR){

                        jQuery("#progressbar").progressbar({  
                                        value:100 
                        }); 
                        window.location.href = page + "&status=fail&msg=" + encodeURIComponent(jqXHR.status + ": " + jqXHR.statusText);
                }
        });        
             
}      
                        
function getTimeStamp(){
        return new Date().getTime();  
}