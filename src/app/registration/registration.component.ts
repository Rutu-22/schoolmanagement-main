import { Component } from '@angular/core';
import { AbstractControl, FormBuilder, ValidatorFn, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { SharedService } from '../shared.service';
import { ApiService } from '../api.service';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-registration',
  templateUrl: './registration.component.html',
  styleUrls: ['./registration.component.scss']
})
export class RegistrationComponent {
  registrationForm: any;
  submitted: boolean = false;
  academicYears: string[] = this.generateAcademicYears();


  constructor(private formBuilder: FormBuilder, private router: Router,private sharedService: SharedService, private apiService: ApiService ,private http:HttpClient) {
    this.registrationForm = this.formBuilder.group({
      generalRegisterNumber: ['', [Validators.required, Validators.pattern('^[0-9]+$')]],
      studentId: ['', [Validators.required, Validators.pattern('^[0-9]+$')]],
      adharCard: ['', [Validators.required, Validators.pattern('^[0-9]{12}$')]],
      fullName: ['', [Validators.required,Validators.pattern('^[a-zA-Z ]+$')]],
      motherName: ['', [Validators.required,Validators.pattern('^[a-zA-Z ]+$')]],
      nationality: ['Indian', [Validators.required, Validators.pattern('^[a-zA-Z ]+$')]],
      address: ['', Validators.required],
      mobileNo: ['', [Validators.required,  Validators.pattern('^[0123456789]{10}$')]],
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
    const academicYear = this.sharedService.getSelectedAcademicYear();
    this.registrationForm.patchValue({
      academicYear: academicYear ||null,

    });
   
  }
  generateAcademicYears(): string[] {
    const startYear = 1970;
    const currentYear = new Date().getFullYear();
    const years: string[] = [];
    for (let year = startYear; year < currentYear + 10; year++) {
      years.push(`${year}-${year + 1}`);
    }
    return years;
  }
  

  get formControls() {
    return this.registrationForm.controls;
  }
   
  submitForm() {
    this.submitted = true;
  
    if (this.registrationForm.invalid) {
      return;
    }
    const formData = this.registrationForm.value;
    const queryParams = { ...this.registrationForm.value };
    this.router.navigate(['/student-details'], { queryParams });
  
    // Call the API service to add the student
    this.apiService.addStudent(this.registrationForm.value).subscribe(
      response => {
        if (typeof response === 'string') {
          console.log('Student added successfully', response);
        } else {
          console.log('Unexpected response format:', response);
        }
        // Optionally, you can navigate to a different page or perform other actions here
      },
      error => {
        console.error('Error adding student data', error);
      }
    );
  }
  
 
}

