import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Params } from '@angular/router';
import { Student } from '../student.model';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-bonafide-certificate',
  templateUrl: './bonafide-certificate.component.html',
  styleUrls: ['./bonafide-certificate.component.scss']
})
export class BonafideCertificateComponent implements OnInit {
  student: any;
    filteredStudents: any[] = [];


  constructor(private route: ActivatedRoute,private apiService: ApiService) { }
  

  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      const studentId = params['studentId'];
    });
  }
  
}
