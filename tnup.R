tpup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    get_url<-getURL("http://bud.tainan.gov.tw/doc/CityBoard_new2.aspx", encoding="utf-8")
    get_url_parse<-htmlParse(get_url, encoding="utf-8")
    
    item <- xpathSApply(get_url_parse,"//table[@class='p1']/tr/td/a",xmlValue)
    url <- xpathSApply(get_url_parse,"//table[@class='p1']/tr/td/a[@href]",xmlAttrs)
    link <- data.frame(item,url[1,],stringsAsFactors = FALSE)
    
    for(i in 1:nrow(link)){
        filetype <- c(filetype, checkfiletype(weblist[i,1]))  
    }
    
    write.csv(link,"tn_link_list.csv",row.names=FALSE)
}

checkfiletype <- function(txt){
    if(grepl(".*pdf", txt)==TRUE) {
        return("pdf")
    } else if(grepl(".*doc", txt)==TRUE) {
        return("doc")
    }
}