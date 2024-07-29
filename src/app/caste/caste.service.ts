import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from 'src/environments/environment';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CasteService {
  httpOptions = {
    headers: new HttpHeaders({
      "Content-Type": "application/json",
    }),
  };
  constructor(private http:HttpClient) {}

  getCastes(): Observable<any> {
    return this.http.get(environment.ApiUrl + 'getCastes');
  }

  addCaste(casteData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'addCaste', JSON.stringify(casteData),this.httpOptions);
  }

updateCaste(casteId: number, casteData: any): Observable<any> {
    return this.http.post<any>(`${environment.ApiUrl}updateCaste/${casteId}`, casteData);
  }
  

  deleteCaste(casteData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'deleteCaste', casteData);
  }

}
