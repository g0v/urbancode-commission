record_parse<-function(target){
    library(bitops)
    library(RCurl)
    library(XML)
    library(plyr)
    
    target<<-target
    
    dir.name<-paste("./record/TPEUP/html/tpup",target,".pdf/",sep="")
    
    flist.df<-data.frame(fname=gsub("^(.*?)\\..*","\\1",dir(dir.name)),ftype=gsub(".*\\.(.*?)$","\\1",dir(dir.name)),stringsAsFactors = FALSE)
    flist.df$docuID<-as.integer(substr(flist.df[,1],5,length(flist.df[,1])))
    flist.df<-subset(flist.df,flist.df[,2]=="html")
    flist.df<-arrange(flist.df,flist.df[,3])
    flist.df<-flist.df[complete.cases(flist.df),]
    
    for(i in 1:nrow(flist.df)){
        get_url<-paste("./record/TPEUP/html/tpup",target,".pdf/",flist.df[i,1],".",flist.df[i,2],sep="")
        
        parse.df<-html_parse(get_url,mode=1)
        
        if(!exists("parse.done")){parse.done<-parse.df}else{parse.done<-rbind(parse.done,parse.df)}
    }
    
    page.n<-grep("^第(.*)?頁$",parse.done[,1])
    if(length(page.n)==0){
        parse.done<-parse.done[-grep("^(-)?( )?([0-9])?([0-9])?([0-9])( )?(-)?$",parse.done[,1]),]
    } else {
        parse.done<-parse.done[-grep("^第(.*)頁$",parse.done[,1]),]
    }
    
    zh_number_cap<<-c("壹","貳","參","肆","伍","陸","柒","捌","玖","拾")
    zh_number<<-c("一","二","三","四","五","六","七","八","九","十")
    item_title<-c("報告事項","審議事項","臨時動議")
    
    item.ind1<-c(1,grep(paste("^",zh_number_cap,"、",collapse="|",sep=""),parse.done[,1]),nrow(parse.done)+1)
    
    if(length(item.ind1)==2){
        item.ind1<-c(1,grep(paste("^",item_title,"$",collapse="|",sep=""),parse.done[,1]),nrow(parse.done),nrow(parse.done)+1)
    }
    
    item.ind2<-grep("散會",parse.done[,1])
    item.ind<-unique(c(item.ind1,item.ind2))
    item.ind<-item.ind[order(item.ind)]
    
    item.cnt<-length(item.ind)-1
    
    
    item.list<-rep(list(NULL),item.cnt)
    
    for(i in 1:item.cnt){
        item.list[[i]]<-parse.done[item.ind[i]:(item.ind[i+1]-1),]
    }
    
    names(item.list)<-parse.done[item.ind[-length(item.ind)],1]
    
    item.list[[1]]<-item.list[[1]][,1]
    
    item.list[2:item.cnt]<-lapply(item.list[2:item.cnt],item_parse)
    
    return(item.list)
}

html_parse<-function(f.path,mode){
    html.parse<-htmlParse(f.path,encoding="utf-8")
    
    parse.pos<-xpathSApply(html.parse,"//div[@class='txt']",getNodePosition)
    parse.file<-as.integer(gsub(".*/page(.*).html:.*","\\1",parse.pos))
    parse.line<-as.integer(gsub(".*:(.*)$","\\1",parse.pos))
    
    if(mode==1){
        parse.txt<-xpathSApply(html.parse,"//div[@class='txt']",xmlValue)
        
        parse.df<-data.frame(txt=parse.txt,file=parse.file,line=parse.line,stringsAsFactors=FALSE)
    } 
    else if(mode==2){
        txt.lt<-xpathSApply(html.parse,"//div[@class='txt']",xmlChildren,addNames=FALSE)
        txt.vector<-rapply(lapply(txt.lt,toString.XMLNode),c)
        for(i in 1:length(txt.vector)){txt.vector[i]<-gsub("(\\[\\[[0-9]\\]\\])|\\n","",strsplit(txt.vector,"attr")[[i]][1])}
        
        parse.loc<-xpathSApply(html.parse,"//div[@class='txt']",xmlAttrs)
        parse.loc.left<-as.integer(gsub(".*left:(.*?)px;.*","\\1",parse.loc[2,]))
        parse.loc.top<-as.integer(gsub(".*top:(.*?)px;.*","\\1",parse.loc[2,]))
        
        parse.df<-data.frame(txt=txt.vector,loc.left=parse.loc.left,loc.top=parse.loc.top,file=parse.file,line=parse.line,stringsAsFactors=FALSE)
    }
    
    return(parse.df)
}

item_parse<-function(item.df){
    case.ind<-c(grep("^報告事項|^審議事項",item.df[,1]),nrow(item.df)+1)
    case.cnt<-length(case.ind)-1
    
    case.list<-rep(list(NULL),case.cnt)
    
    if(case.cnt==0){
        case.list[[1]]<-case_parse(item.df)
    } else {
        for(i in 1:case.cnt){
            case.list[[i]]<-item.df[case.ind[i]:(case.ind[i+1]-1),]
        }
        case.list<-lapply(case.list,case_parse)
    }    
    
    return(case.list)
}

case_parse<-function(case.df){
    table.ind<-grep("意見綜理表$",case.df[,1])
    
    if(length(table.ind)!=0){
        content.list<-list(case.df[1:(table.ind-1),],case.df[table.ind:nrow(case.df),])
    } else {
        content.list<-list(case.df)
    }
    
    section.ind<-c(1,grep("說明(：|:)$|^決議(：|:)|^附帶決議(：|:)",content.list[[1]][,1]),nrow(content.list[[1]])+1)
    section.cnt<-length(section.ind)-1
    
    section.list<-rep(list(NULL),section.cnt)
    names(section.list)<-content.list[[1]][section.ind[1:(length(section.ind))-1],1]
    
    for(i in 1:section.cnt){
        section.list[[i]]<-content.list[[1]][(section.ind[i]+1):(section.ind[i+1]-1),]
    }
    
    content.list[[1]]<-section.list
    
    content.list[[1]]<-lapply(content.list[[1]],paragraph_parse)
    
    if(length(table.ind)!=0){content.list[[2]]<-table_parse(content.list[[2]])}
    
    return(content.list)
}

paragraph_parse<-function(section.df){
    section.df[,1]<-sub("。$","。/n",section.df[,1])
    para.ind<-c(grep(paste("^(十)?",zh_number,"、",collapse="|",sep=""),section.df[,1]),nrow(section.df)+1)
    para.cnt<-length(para.ind)-1
        
    para.list<-rep(list(NULL),para.cnt)
   
    if(para.cnt==0){
        para.list[[1]]<-paste(section.df[,1],collapse="",sep="")
        para.list[[1]]<-gsub("。/n$","。",para.list[[1]])
    } else {
        for(i in 1:para.cnt){
            para.list[[i]]<-paste(section.df[para.ind[i]:(para.ind[i+1]-1),1],collapse="",sep="")
            para.list[[i]]<-gsub("。/n$","。",para.list[[i]])
        }
    }
    
    return(para.list)
}

table_parse<-function(table.df){
    start.pg<-min(unique(table.df[,2]))
    end.pg<-max(unique(table.df[,2]))
    
    for(i in start.pg:end.pg){
        get_url<-paste("./record/TPEUP/html/tpup",target,".pdf/page",i,".html",sep="")
        
        table.pg<-html_parse(get_url,mode=2)
        
        if(i==start.pg){table.pg<-table.pg[table.pg$line>table.df[1,3],]}
        if(i==end.pg){table.pg<-table.pg[table.pg$line<=table.df[nrow(table.df),3],]}
        
        if(!exists("table.done")){table.done<-table.pg}else{table.done<-rbind(table.done,table.pg)}
    }
    
    table.done[,1]<-paste("<div>",table.done[,1],"</div>",sep="")
    
    write.csv(table.done,file="cache.csv")
    table.done<-read.csv("cache.csv",stringsAsFactors=FALSE,encoding="BIG5")[,-1]
    
    txt_c1<-vector(mode="character",length=nrow(table.done))
    txt_c2<-vector(mode="character",length=nrow(table.done))
    
    to.left<-min(table.done[,2])
    
    for(i in 1:nrow(table.done)){
        table_txt<-iconv(table.done[i,1],"BIG5","UTF-8")
        table_txt<-gsub("<U\\+(.*?)>","(\\1)",table_txt)
        
        txt_parse<-xmlParse(table_txt,encoding="UTF-8")
        txt_block<-xpathSApply(txt_parse,"//span",xmlValue)
        
        ##print(i) ##line for debugging
        
        if(table.done[i,2]==to.left){
            txt_c1[i]<-txt_block[1]
            txt_c2[i]<-paste(txt_block[2:length(txt_block)],collapse="")
        } else {
            txt_c1[i]<-NA
            txt_c2[i]<-paste(txt_block,collapse="")
        }
    }
    
    table.ex<-data.frame(item=txt_c1,content=txt_c2,stringsAsFactors = FALSE)
    
    table.ex<-table.ex[-grep("^(-)?( )?([0-9])?([0-9])?([0-9])( )?(-)?$",table.ex[,2]),]
    if(length(grep("^NA",table.ex[,2]))>0){
        table.ex[grep("^NA",table.ex[,2]),2]<-NA
    }
    
    pet.ind<-c(1,grep("陳情人",table.ex[,2]),nrow(table.ex)+1)
    pet.cnt<-length(pet.ind)-1
    
    pet.list<-rep(list(NULL),pet.cnt)
    
    for (i in 1:pet.cnt){
        pet.list[[i]]<-table.ex[pet.ind[i]:(pet.ind[i+1]-1),]
    }
    
    pet.list<-lapply(pet.list,table.pet_parse)
    
    return(pet.list)
}

table.pet_parse<-function(pet.df){
    c.ind<-c(1,grep("。$|？$|！$|：$",pet.df[,2])+1)
    c.cnt<-length(c.ind)-1
    
    c.list<-rep(list(NULL),c.cnt)
    
    if(c.cnt==0){c.list[[1]]<-pet.df} else{
        for (i in 1:c.cnt){
            c.list[[i]]<-pet.df[c.ind[i]:(c.ind[i+1]-1),]
        }
    }
    
    c.list<-lapply(c.list,table.sec_parse)
    
    return(c.list)
}

table.sec_parse<-function(c.df){
    l.define<-c("陳( )?情( )?理( )?由( )?","建( )?議( )?辦( )?法( )?","市( )?府( )?回( )?應( )?","說( )?明( )?","委( )?員( )?會( )?決( )?議( )?")
    l.ind<-grep(paste("^",l.define,collapse="|",sep=""),c.df[,1])
    
    if(length(l.ind)!=0){
        for(i in 1:length(l.ind)){
            c.df[l.ind[i],2]<-paste(gsub(paste("^",l.define,collapse="|",sep=""),"",c.df[l.ind[i],1]),c.df[l.ind[i],2],sep="")
            c.df[l.ind[i],1]<-gsub(gsub(paste("^",l.define,collapse="|",sep=""),"\\1",c.df[l.ind[i],1]),"",c.df[l.ind[i],1])
        }
    }
    
    c.vector<-gsub("NA","",paste(c.df[,2],collapse=""))
    
    return(c.vector)
}