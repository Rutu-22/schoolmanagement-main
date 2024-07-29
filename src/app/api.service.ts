import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from 'src/environments/environment';
import { Observable, catchError, throwError } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  constructor(private http: HttpClient) {}

  getStudents() {
    return this.http.get(environment.ApiUrl + 'getStudents');
  }

  addStudent(studentData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'addStudent', studentData);
  }

  updateStudent(studentData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'updateStudent', studentData);
  }

  deleteStudent(studentId: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'deleteStudent', { studentId });
  }
}
