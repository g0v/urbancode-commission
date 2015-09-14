tpup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    get_url<-getURL("http://www.tupc.gov.taipei/lp.asp?CtNode=6308&CtUnit=4388&BaseDSD=7&mp=120021&nowPage=1&pagesize=200", encoding="utf-8")
    get_url_parse<-htmlParse(get_url, encoding="utf-8")
        
    link<-xpathSApply(get_url_parse,"//div[@class='list']/ul/li/a",xmlAttrs)
    link<-data.frame(weblink=link[1,],record_name=link[2,],stringsAsFactors = FALSE)
    
    indi1<-iconv("第","utf-8","big5")
    indi2<-iconv("次","utf-8","big5")
    indi.times<-paste(".*",indi1,"([0-9][0-9][0-9])",indi2,".*",sep="")
    indi.con<-iconv("續","utf-8","big5")
    
    
    for(i in 1:nrow(link)){
        link[i,1]<-paste("http://www.tupc.gov.taipei/",link[i,1],sep="")
        
        txt<-link[i,2]
        txt<-gsub(">","",gsub("<","%",txt))
        txt<-iconv(URLdecode(txt),"utf-8","big5")
        
        link[i,2]<-paste("tpup",gsub(indi.times,"\\1",txt),sep="")        
        
        if(grepl("indi.con",txt)) {link[i,2]<-paste(link[i,2],"_2",sep="")}        
    }
    
    write.csv(link,"./record/TPEUP/tp_web_list.csv",row.names=FALSE)
}

tpup_parse2<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    weblist<-read.csv("./record/TPEUP/tp_web_list.csv",stringsAsFactors = FALSE)
        
    filetype<-c()
    pb <- txtProgressBar(max = nrow(weblist), style = 3)
    
    for(i in 1:nrow(weblist)){
        if(grepl("doc|pdf", weblist[i,1])==FALSE){
            get_url <- getURL(weblist[i,1], encoding="utf-8")
            get_url_parse <- htmlParse(get_url, encoding="utf-8")
            node_parse <- xpathApply(get_url_parse, "//div[@class='download']/ul/li", xmlChildren)
            txt_parse <- toString.XMLNode(node_parse)
            weblist[i,1] <- sub(".*?href=\"(.*?)\".*", "\\1", txt_parse)
            weblist[i,1] <- paste("http://www.tupc.gov.taipei/",weblist[i,1],sep="")
        } else {
            weblist[i,2] <- sub("(.*?),.*","\\1",weblist[i,2])
        }
        filetype <- c(filetype, checkfiletype(weblist[i,1]))  
        
        setTxtProgressBar(pb, i)
    }
    weblist <- cbind(weblist,filetype)
    
    write.csv(weblist,"./record/TPEUP/tpup_link_list.csv",row.names=FALSE)
}

checkfiletype <- function(txt){
    if(grepl(".*pdf", txt)==TRUE) {
        return("pdf")
    } else if(grepl(".*doc", txt)==TRUE) {
        return("doc")
    }
}

tp_dlrecord<-function(csvfile){
    library(httr)
    
    link.list<-read.csv(csvfile,stringsAsFactors=FALSE)
    
    pb <- txtProgressBar(max = nrow(link.list), style = 3)
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list[i,1])
        filelocale<-paste("./record/TPEUP/raw/",link.list[i,2],".",link.list[i,3],sep="")
        download.file(link$url,filelocale,mode="wb")
        
        setTxtProgressBar(pb, i)
    }    
}

tp_pdf_to_html<-function(){
    if(!file.exists("./record/TPEUP/html")){
        dir.create("./record/TPEUP/html/")
    }
    
    file_list <- dir("./record/TPEUP/raw/")
    
    for(i in 1:length(file_list)){
        file_type <- substr(file_list[i], nchar(file_list[i])-2, nchar(file_list[i]))
        
        if(file_type == "pdf"){
            dir_name <- paste("./record/TPEUP/html/", file_list[i],sep="")
            uri <- paste("./record/TPEUP/raw/", file_list[i], sep="")
            system(paste('"pdftohtml.exe"',uri, dir_name)) 
        }
    }
}



tp_pdf_to_txt<-function(){
    library(tm)
    
    if(!dir.exists("./record/TPEUP/txt/")){
        dir.create("./record/TPEUP/txt/")
    }
    file_list <- dir("./record/TPEUP/raw/")
    
    for(i in 1:length(file_list)){
        file_type <- substr(file_list[i], nchar(file_list[i])-2, nchar(file_list[i]))
        
        if(file_type == "pdf"){
            uri <- paste("./record/TPEUP/raw/", file_list[i], sep="")
            pdf <- readPDF(control= list(text="-layout"))(elem = list(uri = uri), language="en")
            
            write(content(pdf), paste("./record/TPEUP/txt/tpup", gsub(".*?([0-9]+).*","\\1",file_list[i]), ".txt", sep=""))
        }
    }
}

