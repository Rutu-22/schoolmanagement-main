import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from 'src/environments/environment';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class DivisionService {

  constructor(private http: HttpClient) {}

  getDivisions(): Observable<any> {
    return this.http.get(environment.ApiUrl + 'getDivisions');
  }

  addDivision(divisionData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'addDivision', divisionData);
  }

  updateDivision(divisionId: number, divisionData: any): Observable<any> {
    return this.http.post<any>(`${environment.ApiUrl}updateDivision/${divisionId}`, divisionData);
  }

  deleteDivision(divisionData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'deleteDivision', divisionData);
  }


}
