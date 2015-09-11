mass_parse<-function(region,start,end){
    n.vector<-c(start:end)
    f.vector<-vector(mode="character",length=length(n.vector))
    
    for(i in start:end){
        print(i)
        json_convert(record_parse(i),i)
    }
}

record_merge<-function(region,start,end){
    n.vector<-c(start:end)
    f.vector<-vector(mode="character",length=length(n.vector))
    
    
    for(i in 1:length(n.vector)){
        f.name<-paste("./record/TPEUP/JSON/",as.character(n.vector[i]),".json",sep="")
        f.vector[i]<-paste(readLines(f.name),collapse="",sep="")
        
    }
    
    jsontxt<-paste("[",paste(f.vector,collapse=",",sep=""),"]",sep="")
    
    ex.name<-paste("./record/TPEUP/TPEUP_",start,"_",end,".json",sep="")
    file.create(ex.name)
    
    writeLines(jsontxt,con=ex.name,useBytes=TRUE)
}