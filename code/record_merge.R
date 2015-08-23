record_merge<-function(region,start,end){
    n.vector<-c(start:end)
    f.vector<-vector(mode="character",length=length(n.vector))
    
    for(i in 1:length(n.vector)){
        f.name<-paste(as.character(n.vector[i]),".json",sep="")
        f.vector[i]<-readLines(f.name)
    }
    
    jsontxt<-paste("[",paste(f.vector,collapse=",",sep=""),"]",sep="")
    
    write(jsontxt,file="./record/TPEUP/TPEUP.json")
}