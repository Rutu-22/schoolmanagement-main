import { Injectable } from '@angular/core';
import { Student } from './student.model';
import { environment } from 'src/environments/environment';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class StudentService {
  // private student: Student[] = [];

  // addStudent(student: Student): void {
  //   this.student.push(student);
  // }

  // getAllStudents(): Student[] {
  //   return this.student;
  // }


  // getStudentsByYear(academicYear: string): Student[] {
  //   return this.student.filter(student => student.academicYear === academicYear);
  // }
  constructor(private http:HttpClient){

  }
  gets(){
     return this.http.get(environment.ApiUrl+'getStudents');
  }
  
}
