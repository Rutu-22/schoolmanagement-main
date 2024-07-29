import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { environment } from "src/environments/environment";
@Injectable({
    providedIn: 'root',
  })
export class SchoolsService{
    constructor(private http:HttpClient){

    }
    gets(){
       return this.http.get(environment.ApiUrl+'Getschools');
    }
}