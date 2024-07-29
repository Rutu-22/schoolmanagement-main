import { ApiService } from './../api.service';
import { Component, EventEmitter, Input, OnInit, Output, TemplateRef, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { Student } from '../student.model';
import { StudentService } from '../student.service';
import { SharedService } from '../shared.service';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-student-details',
  templateUrl: './student-details.component.html',
  styleUrls: ['./student-details.component.scss']
})
export class StudentDetailsComponent implements OnInit {
  @Input() student: any;
  registrationForm: FormGroup; // Define the form group
  isEditMode: boolean = false;
  displayRow: boolean = true;
  filteredStudents: any[] = [];
  searchName: string = '';
  searchAcademicYear: string = '';
    @ViewChild('editRow') editRow!: TemplateRef<any>;
    selectedStudent: Student | null = null; ;
    showStudentDetails: boolean = false;
    showStudentForm: boolean = false;

    constructor(private studentService: StudentService,private route: ActivatedRoute,private sharedService: SharedService, private formBuilder: FormBuilder,private apiService: ApiService ,private http:HttpClient,private router: Router) {
      this.registrationForm = this.formBuilder.group({
        generalRegisterNumber: ['', [Validators.required, Validators.pattern('^[0-9]+$')]],
        studentId: ['', [Validators.required, Validators.pattern('^[0-9]+$')]],
        adharCard: ['', [Validators.required, Validators.pattern('^[0-9]{12}$')]],
        fullName: ['', [Validators.required,Validators.pattern('^[a-zA-Z ]+$')]],
        motherName: ['', [Validators.required,Validators.pattern('^[a-zA-Z ]+$')]],
        address: ['', Validators.required],
        nationality: ['Indian', [Validators.required, Validators.pattern('^[a-zA-Z ]+$')]],
        mobileNo: ['', [Validators.required,  Validators.pattern('^[0-9]{10}$')]],
        inputEmailAddress: ['', [Validators.required,Validators.email]],
        dateOfBirth: ['', Validators.required],
        placeOfBirth: ['', Validators.required],
        Gender: ['', Validators.required],
        previousSchool: ['', Validators.required],
        reasonForLeaving: ['', Validators.required],
        leftStandard: ['', Validators.required],
        admissionDate: ['', Validators.required],
        academicYear: ['', Validators.required],
        classOfAdmission: ['', Validators.required],
        division: ['', Validators.required],
        cast: ['', Validators.required],
        religion: ['', Validators.required]
      });
    }
    
    ngOnInit() {
      this.route.queryParams.subscribe((params: Params) => {
        this.student = params;
      });
    
    }
  
   
    
    trackByIndex(index: number): number {
      return index;
    }
  
    editStudent(student: Student) {
      this.isEditMode = true;
      this.selectedStudent = student;
      this.registrationForm.patchValue(student);

    }
  
    saveStudent() {
      if (this.registrationForm.invalid) {
        return;
      }
    
      const updatedStudentData = this.registrationForm.value;
      this.apiService.updateStudent(updatedStudentData).subscribe(
        response => {
          // Handle success response
          const updatedIndex = this.filteredStudents.findIndex(student => student.id === this.selectedStudent!.id);
          if (updatedIndex !== -1) {
            this.filteredStudents[updatedIndex] = { ...this.selectedStudent, ...updatedStudentData };
          }
    
          this.isEditMode = false;
          this.registrationForm.reset();
        },
        error => {
          console.error('Error updating student:', error);
          // Handle error response if needed
        }
      );
    }
    
  
    
  
    cancelEdit() {
      this.isEditMode = false;
      this.registrationForm.reset();
    }
  
    deleteStudent(generalRegisterNumber: number) {
      this.apiService.deleteStudent(generalRegisterNumber).subscribe(() => {
        this.filteredStudents = this.filteredStudents.filter(student => student.generalRegisterNumber !== generalRegisterNumber);
      });
    }
  
    searchStudents() {
      this.filteredStudents = this.student.filter(student => 
        student.firstName.toLowerCase().includes(this.searchName.toLowerCase()) &&
        student.academicYear.toLowerCase().includes(this.searchAcademicYear.toLowerCase())
      );
    }
    navigateToBonafide(student: Student) {
      this.router.navigate(['/bonafide-certificate'], {
        queryParams: { studentId: student.id }
      });
    }

 }
